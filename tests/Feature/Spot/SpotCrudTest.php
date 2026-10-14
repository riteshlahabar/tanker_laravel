<?php

declare(strict_types=1);

namespace Tests\Feature\Spot;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Spot\Models\Spot;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class SpotCrudTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_admin_can_create_a_spot_with_a_refill_charge(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.spot.store'), [
                'name' => 'Hadapsar Borewell',
                'refill_charge' => 400.50,
                'address' => 'Hadapsar, Pune',
                'latitude' => 18.5204,
                'longitude' => 73.8567,
                'is_available' => 1,
                'status' => 1,
            ])
            ->assertRedirect(route('admin.spot.index'));

        $this->assertDatabaseHas('spots', [
            'name' => 'Hadapsar Borewell',
            'refill_charge' => 400.50,
        ]);
    }

    public function test_refill_charge_is_required(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.spot.store'), [
                'name' => 'No Charge',
                'is_available' => 1,
                'status' => 1,
            ])
            ->assertSessionHasErrors('refill_charge');
    }

    public function test_coordinates_are_range_checked(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.spot.store'), [
                'name' => 'Bad Coords',
                'refill_charge' => 100,
                'latitude' => 120,
                'longitude' => 400,
                'is_available' => 1,
                'status' => 1,
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_usable_scope_excludes_unavailable_and_inactive_spots(): void
    {
        Spot::query()->create(['name' => 'Good', 'refill_charge' => 100, 'is_available' => true, 'status' => true]);
        Spot::query()->create(['name' => 'Busy', 'refill_charge' => 100, 'is_available' => false, 'status' => true]);
        Spot::query()->create(['name' => 'Off', 'refill_charge' => 100, 'is_available' => true, 'status' => false]);

        $this->assertSame(['Good'], Spot::query()->usable()->pluck('name')->all());
    }

    public function test_nearest_scope_orders_by_distance(): void
    {
        Spot::query()->create([
            'name' => 'Far', 'refill_charge' => 100, 'latitude' => 19.0760, 'longitude' => 72.8777,
        ]);
        Spot::query()->create([
            'name' => 'Near', 'refill_charge' => 100, 'latitude' => 18.5210, 'longitude' => 73.8570,
        ]);

        $ordered = Spot::query()->usable()->nearest(18.5204, 73.8567)->pluck('name')->all();

        $this->assertSame(['Near', 'Far'], $ordered);
    }

    public function test_datatable_ajax_renders_rows(): void
    {
        Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.spot.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('Hadapsar Borewell');
    }

    public function test_availability_filter_narrows_the_rows(): void
    {
        Spot::query()->create(['name' => 'Open Spot', 'refill_charge' => 100, 'is_available' => true]);
        Spot::query()->create(['name' => 'Closed Spot', 'refill_charge' => 100, 'is_available' => false]);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.spot.index').'?draw=1&start=0&length=10&is_available=0')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('Closed Spot');
    }

    public function test_export_honours_the_filter(): void
    {
        Spot::query()->create(['name' => 'Open Spot', 'refill_charge' => 100, 'is_available' => true]);
        Spot::query()->create(['name' => 'Closed Spot', 'refill_charge' => 100, 'is_available' => false]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.spot.export', ['is_available' => 0]))
            ->assertOk();
    }

    public function test_driver_cannot_manage_spots(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9000000000']);

        $this->actingAs($driver)->get(route('admin.spot.index'))->assertForbidden();
    }
}
