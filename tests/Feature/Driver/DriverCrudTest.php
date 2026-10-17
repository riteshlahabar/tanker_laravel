<?php

declare(strict_types=1);

namespace Tests\Feature\Driver;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class DriverCrudTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_create_form_only_offers_unassigned_tankers(): void
    {
        $free = Tanker::query()->create(['vehicle_name' => 'Free One', 'vehicle_number' => 'MH01AA1111']);
        $taken = Tanker::query()->create(['vehicle_name' => 'Taken One', 'vehicle_number' => 'MH02BB2222']);

        $otherDriver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $taken->assignments()->create([
            'driver_id' => $otherDriver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.driver.create'))
            ->assertOk()
            ->assertSee($free->vehicle_number)
            ->assertDontSee($taken->vehicle_number);
    }

    public function test_creating_a_driver_assigns_the_chosen_tanker(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH01AA1111']);

        $this->actingAs($this->adminUser)
            ->post(route('admin.driver.store'), [
                'first_name' => 'Ramesh',
                'last_name' => 'Patil',
                'mobile' => '9876543210',
                'password' => 'driver-pass-1',
                'password_confirmation' => 'driver-pass-1',
                'tanker_id' => $tanker->id,
                'monthly_salary' => 15000,
                'status' => 1,
            ])
            ->assertRedirect(route('admin.driver.index'));

        $driver = User::query()->where('mobile', '9876543210')->firstOrFail();

        $this->assertTrue($driver->hasRole(RoleEnum::Driver->value));
        $this->assertSame($tanker->id, $driver->currentAssignment->tanker_id);
        $this->assertDatabaseHas('tanker_assignments', [
            'driver_id' => $driver->id,
            'tanker_id' => $tanker->id,
            'assigned_to' => null,
        ]);
    }

    public function test_changing_the_tanker_closes_the_old_assignment_and_keeps_history(): void
    {
        $first = Tanker::query()->create(['vehicle_name' => 'First', 'vehicle_number' => 'MH01AA1111']);
        $second = Tanker::query()->create(['vehicle_name' => 'Second', 'vehicle_number' => 'MH02BB2222']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);

        $first->assignments()->create([
            'driver_id' => $driver->id,
            'assigned_from' => now()->subDays(5)->toDateString(),
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.driver.tanker.update', $driver), [
                'tanker_id' => $second->id,
                'reason' => 'Driver absent',
            ])
            ->assertRedirect(route('admin.driver.index'));

        $this->assertSame($second->id, $driver->refresh()->currentAssignment->tanker_id);
        $this->assertSame(2, $driver->assignments()->count());
        $this->assertDatabaseHas('tanker_assignments', [
            'tanker_id' => $first->id,
            'reason' => 'Driver absent',
            'assigned_to' => now()->toDateString(),
        ]);
    }

    public function test_a_tanker_cannot_be_run_by_two_drivers(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH01AA1111']);
        $first = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $second = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);

        $tanker->assignments()->create([
            'driver_id' => $first->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.driver.tanker.update', $second), ['tanker_id' => $tanker->id]);

        $this->assertNull($first->refresh()->currentAssignment);
        $this->assertSame($tanker->id, $second->refresh()->currentAssignment->tanker_id);
        $this->assertSame(1, $tanker->currentAssignment()->count());
    }

    public function test_releasing_a_tanker_leaves_the_driver_with_none(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH01AA1111']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);
        $tanker->assignments()->create(['driver_id' => $driver->id, 'assigned_from' => now()->toDateString()]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.driver.tanker.update', $driver), ['tanker_id' => '', 'reason' => 'On leave']);

        $this->assertNull($driver->refresh()->currentAssignment);
    }

    public function test_blank_password_on_update_keeps_the_existing_one(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);
        $original = $driver->password;

        $this->actingAs($this->adminUser)
            ->put(route('admin.driver.update', $driver), [
                'first_name' => 'Renamed',
                'mobile' => '9876543210',
                'monthly_salary' => 20000,
                'password' => '',
                'status' => 1,
            ])
            ->assertRedirect(route('admin.driver.index'));

        $this->assertSame($original, $driver->refresh()->password);
        $this->assertSame('Renamed', $driver->first_name);
    }

    public function test_a_customer_id_cannot_be_edited_through_the_driver_module(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $this->actingAs($this->adminUser)
            ->get(route('admin.driver.edit', $customer->id))
            ->assertNotFound();
    }

    public function test_deactivating_a_driver_revokes_app_tokens(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);
        $driver->createToken('access:phone');

        $this->assertSame(1, $driver->tokens()->count());

        $this->actingAs($this->adminUser)
            ->putJson(route('admin.driver.status', $driver), ['status' => 0])
            ->assertOk();

        $this->assertSame(0, $driver->tokens()->count());
    }

    public function test_datatable_ajax_renders_rows(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH01AA1111']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);
        $tanker->assignments()->create(['driver_id' => $driver->id, 'assigned_from' => now()->toDateString()]);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.driver.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('MH01AA1111');
    }

    public function test_driver_export_downloads(): void
    {
        $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);

        $this->actingAs($this->adminUser)
            ->get(route('admin.driver.export'))
            ->assertOk();
    }
}
