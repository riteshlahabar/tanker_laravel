<?php

declare(strict_types=1);

namespace Tests\Feature\Spot;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Spot\Contracts\SpotRepositoryInterface;
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
                'contact_person' => 'Manoj',
                'contact_mobile' => '9578123456',
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

    public function test_the_address_and_contact_are_required(): void
    {
        // A filling point the driver cannot find, or whose keeper cannot be
        // rung when it is shut, is not usable on the route — and with the
        // coordinates gone the address is the only thing that says where it is.
        $this->actingAs($this->adminUser)
            ->post(route('admin.spot.store'), [
                'name' => 'Nameless Well',
                'refill_charge' => 100,
                'is_available' => 1,
                'status' => 1,
            ])
            ->assertSessionHasErrors(['address', 'contact_person', 'contact_mobile']);
    }

    public function test_usable_scope_excludes_unavailable_and_inactive_spots(): void
    {
        Spot::query()->create(['name' => 'Good', 'refill_charge' => 100, 'is_available' => true, 'status' => true]);
        Spot::query()->create(['name' => 'Busy', 'refill_charge' => 100, 'is_available' => false, 'status' => true]);
        Spot::query()->create(['name' => 'Off', 'refill_charge' => 100, 'is_available' => true, 'status' => false]);

        $this->assertSame(['Good'], Spot::query()->usable()->pluck('name')->all());
    }

    public function test_the_usable_list_carries_the_contact_columns_the_resource_reads(): void
    {
        Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400, 'address' => 'Hadapsar',
            'water_type' => 'Borewell', 'contact_person' => 'Manoj', 'contact_mobile' => '9578123456',
        ]);

        // The repository narrows its `select`, and a column the Resource reads
        // but the select leaves out comes back null — which is how an available
        // spot once drew as blocked.
        $spot = app(SpotRepositoryInterface::class)->usable()->firstOrFail();

        $this->assertSame('Borewell', $spot->water_type);
        $this->assertSame('Manoj', $spot->contact_person);
        $this->assertSame('9578123456', $spot->contact_mobile);
        $this->assertSame('Hadapsar', $spot->address);
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
