<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class CustomerCrudTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_admin_can_create_a_customer_who_can_then_sign_in_to_the_app(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), [
                'first_name' => 'Sunita',
                'last_name' => 'Joshi',
                'mobile' => '9876543210',
                'password' => 'customer-pass',
                'password_confirmation' => 'customer-pass',
                'address' => 'Kothrud, Pune',
                'status' => 1,
            ])
            ->assertRedirect(route('admin.customer.index'));

        $customer = User::query()->where('mobile', '9876543210')->firstOrFail();

        $this->assertTrue($customer->hasRole(RoleEnum::Customer->value));
        $this->assertTrue(Hash::check('customer-pass', $customer->password));

        // The account the admin just created works on the app login endpoint.
        $this->postJson(route('api.auth.login'), [
            'username' => '9876543210',
            'password' => 'customer-pass',
        ])->assertOk()->assertJsonPath('data.user.role', RoleEnum::Customer->value);
    }

    public function test_mobile_must_be_unique_across_all_roles(): void
    {
        $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);

        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), [
                'first_name' => 'Clash',
                'mobile' => '9876543210',
                'password' => 'customer-pass',
                'password_confirmation' => 'customer-pass',
                'status' => 1,
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_mobile_must_look_like_a_number(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), [
                'first_name' => 'Bad',
                'mobile' => '12345',
                'password' => 'customer-pass',
                'password_confirmation' => 'customer-pass',
                'status' => 1,
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), [
                'first_name' => 'Mismatch',
                'mobile' => '9876543210',
                'password' => 'customer-pass',
                'password_confirmation' => 'different-pass',
                'status' => 1,
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_a_driver_id_cannot_be_edited_through_the_customer_module(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);

        $this->actingAs($this->adminUser)
            ->get(route('admin.customer.edit', $driver->id))
            ->assertNotFound();
    }

    public function test_datatable_ajax_lists_only_customers(): void
    {
        $this->userWithRole(RoleEnum::Customer, ['mobile' => '9222222222', 'first_name' => 'Sunita']);
        $this->userWithRole(RoleEnum::Driver, ['mobile' => '9333333333', 'first_name' => 'Ramesh']);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.customer.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('Sunita')
            ->assertDontSee('Ramesh');
    }

    public function test_deleting_a_customer_soft_deletes_and_revokes_tokens(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);
        $customer->createToken('access:phone');

        $this->actingAs($this->adminUser)
            ->delete(route('admin.customer.destroy', $customer))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $customer->id]);
        $this->assertSame(0, $customer->tokens()->count());
    }

    public function test_export_downloads(): void
    {
        $this->userWithRole(RoleEnum::Customer, ['mobile' => '9555555555']);

        $this->actingAs($this->adminUser)->get(route('admin.customer.export'))->assertOk();
    }
}
