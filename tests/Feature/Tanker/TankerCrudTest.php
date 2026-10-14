<?php

declare(strict_types=1);

namespace Tests\Feature\Tanker;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TankerCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = $this->userWithRole(RoleEnum::Admin, ['email' => 'owner@example.com']);
    }

    public function test_admin_sees_the_tanker_list(): void
    {
        Tanker::query()->create(['vehicle_name' => 'Tanker One', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAs($this->admin)
            ->get(route('admin.tanker.index'))
            ->assertOk()
            ->assertSee('All Tankers')
            ->assertSee('Export Excel');
    }

    public function test_admin_can_create_a_tanker(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tanker.store'), [
                'vehicle_name' => 'Tanker One',
                'vehicle_number' => 'mh12ab1234',
                'capacity_litres' => 5000,
                'status' => 1,
            ])
            ->assertRedirect(route('admin.tanker.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tankers', [
            'vehicle_name' => 'Tanker One',
            'vehicle_number' => 'MH12AB1234',
            'capacity_litres' => 5000,
        ]);
    }

    public function test_vehicle_number_must_be_unique(): void
    {
        Tanker::query()->create(['vehicle_name' => 'First', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAs($this->admin)
            ->post(route('admin.tanker.store'), [
                'vehicle_name' => 'Second',
                'vehicle_number' => 'MH12AB1234',
                'status' => 1,
            ])
            ->assertSessionHasErrors('vehicle_number');
    }

    public function test_admin_can_update_a_tanker(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'Old', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAs($this->admin)
            ->put(route('admin.tanker.update', $tanker), [
                'vehicle_name' => 'New Name',
                'vehicle_number' => 'MH12AB1234',
                'status' => 1,
            ])
            ->assertRedirect(route('admin.tanker.index'));

        $this->assertSame('New Name', $tanker->refresh()->vehicle_name);
    }

    public function test_status_toggle_returns_json(): void
    {
        $tanker = Tanker::query()->create([
            'vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234', 'status' => true,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.tanker.status', $tanker), ['status' => 0])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse((bool) $tanker->refresh()->status);
    }

    public function test_admin_can_delete_an_unassigned_tanker(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAs($this->admin)
            ->delete(route('admin.tanker.destroy', $tanker))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('tankers', ['id' => $tanker->id]);
    }

    public function test_tanker_assigned_to_a_driver_cannot_be_deleted(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210']);

        $tanker->assignments()->create([
            'driver_id' => $driver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.tanker.destroy', $tanker))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tankers', ['id' => $tanker->id, 'deleted_at' => null]);
    }

    public function test_excel_export_downloads(): void
    {
        Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);

        $response = $this->actingAs($this->admin)->get(route('admin.tanker.export'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', (string) $response->headers->get('content-type'));
    }

    public function test_driver_cannot_reach_the_tanker_module(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9000000000']);

        $this->actingAs($driver)->get(route('admin.tanker.index'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(RoleEnum $role, array $attributes = []): User
    {
        $user = User::query()->create([
            'first_name' => 'Test',
            'password' => 'secret-password',
            'status' => true,
            ...$attributes,
        ]);

        $user->assignRole($role->value);

        return $user;
    }

    public function test_datatable_ajax_renders_rows_with_the_driver_column(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'Tanker One', 'vehicle_number' => 'MH12AB1234']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9876543210', 'first_name' => 'Ramesh']);
        $tanker->assignments()->create(['driver_id' => $driver->id, 'assigned_from' => now()->toDateString()]);

        $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.tanker.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('Tanker One')
            ->assertSee('Ramesh');
    }

    public function test_status_filter_narrows_the_rows(): void
    {
        Tanker::query()->create([
            'vehicle_name' => 'Active One', 'vehicle_number' => 'MH01AA1111', 'status' => true,
        ]);
        Tanker::query()->create([
            'vehicle_name' => 'Idle One', 'vehicle_number' => 'MH02BB2222', 'status' => false,
        ]);

        $this->actingAs($this->admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.tanker.index').'?draw=1&start=0&length=10&status=0')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('Idle One');
    }
}
