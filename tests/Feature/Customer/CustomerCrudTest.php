<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
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
            ->post(route('admin.customer.store'), $this->validPayload())
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
            ->post(route('admin.customer.store'), $this->validPayload(['first_name' => 'Clash']))
            ->assertSessionHasErrors('mobile');
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload([
                'password_confirmation' => 'different-pass',
            ]))
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

    public function test_deleting_a_customer_erases_the_row_and_revokes_tokens(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);
        $customer->createToken('access:phone');

        $this->actingAs($this->adminUser)
            ->delete(route('admin.customer.destroy', $customer))
            ->assertSessionHas('success');

        // Gone outright, not stamped `deleted_at`.
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertSame(0, User::withTrashed()->whereKey($customer->id)->count());
        $this->assertSame(0, $customer->tokens()->count());

        // `model_has_roles.model_id` carries no foreign key to `users`, so
        // nothing in the database would clean this up on its own.
        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $customer->id,
            'model_type' => User::class,
        ]);
    }

    public function test_deleting_a_customer_takes_their_orders_with_them(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444445']);

        $order = Order::query()->create([
            'order_no' => 'TNK-TEST-00001',
            'customer_id' => $customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'order_amount' => 1200,
            'status' => OrderStatusEnum::Delivered,
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.customer.destroy', $customer))
            ->assertSessionHas('success');

        // `orders.customer_id` is cascadeOnDelete. This is the agreed cost of a
        // hard delete, and the reason the confirm dialog spells it out: a
        // delivered order disappears from every report covering that date.
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_the_mobile_number_is_free_again_once_a_customer_is_deleted(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444446']);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.customer.destroy', $customer))
            ->assertSessionHas('success');

        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload(['mobile' => '9444444446']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.customer.index'));
    }

    /**
     * The bug this guards: `users.mobile` is an unconditional unique index, but
     * the rule used to skip trashed rows — so a clash with a soft-deleted
     * account slipped past validation and the insert answered a 500 instead of
     * a field error. Drivers are still soft-deleted, so the case stays live.
     */
    public function test_a_number_held_by_a_soft_deleted_account_is_refused_not_a_500(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9444444447']);
        $driver->delete();

        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload(['mobile' => '9444444447']))
            ->assertSessionHasErrors([
                'mobile' => 'That mobile number is already used by another account. '
                    .'Every account signs in with its own number.',
            ]);
    }

    public function test_the_mobile_number_must_be_exactly_ten_digits(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload(['mobile' => '987654321']))
            ->assertSessionHasErrors([
                'mobile' => 'The mobile number must be exactly 10 digits, with no spaces, '
                    .'dashes or 91 in front.',
            ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload(['mobile' => '919876543210']))
            ->assertSessionHasErrors('mobile');

        // Typed with a space it is still ten digits: prepareForValidation strips them.
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $this->validPayload(['mobile' => '98765 43210']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['mobile' => '9876543210']);
    }

    public function test_the_delivery_address_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['address']);

        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), $payload)
            ->assertSessionHasErrors([
                'address' => 'Enter the delivery address — every order the customer '
                    .'places falls back to it.',
            ]);
    }

    public function test_every_field_answers_in_its_own_words(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.customer.store'), [
                'latitude' => 200,
                'longitude' => 'east',
            ])
            ->assertSessionHasErrors([
                'first_name' => 'Enter the first name.',
                'mobile' => 'Enter the mobile number — it is what the customer signs in with.',
                'password' => 'Set an app password for the customer.',
                'address' => 'Enter the delivery address — every order the customer '
                    .'places falls back to it.',
                'latitude' => 'The latitude must be between -90 and 90.',
                'longitude' => 'The longitude must be a number, for example 73.8567.',
            ]);
    }

    public function test_export_downloads(): void
    {
        $this->userWithRole(RoleEnum::Customer, ['mobile' => '9555555555']);

        $this->actingAs($this->adminUser)->get(route('admin.customer.export'))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'first_name' => 'Sunita',
            'last_name' => 'Joshi',
            'mobile' => '9876543210',
            'password' => 'customer-pass',
            'password_confirmation' => 'customer-pass',
            'address' => 'Kothrud, Pune',
            'can_self_order' => 1,
            'status' => 1,
            ...$overrides,
        ];
    }
}
