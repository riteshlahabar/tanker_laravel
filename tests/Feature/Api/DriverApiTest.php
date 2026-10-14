<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class DriverApiTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $driver;

    private User $customer;

    private Tanker $tanker;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111',
            'per_delivery_rate' => 150,
        ]);
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
        ]);

        $this->tanker = Tanker::query()->create([
            'vehicle_name' => 'Water Tanker 1', 'vehicle_number' => 'MH12AB1234', 'capacity_litres' => 5000,
        ]);
        $this->tanker->assignments()->create([
            'driver_id' => $this->driver->id,
            'assigned_from' => now()->toDateString(),
        ]);
        $this->driver->refresh();

        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400,
            'latitude' => 18.5204, 'longitude' => 73.8567,
        ]);
    }

    public function test_the_summary_gives_the_driver_their_tanker_and_todays_numbers(): void
    {
        $this->order(['status' => OrderStatusEnum::Assigned]);
        $this->order(['status' => OrderStatusEnum::Delivered, 'delivered_at' => now(), 'driver_earning' => 150]);

        $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.summary'))
            ->assertOk()
            ->assertJsonPath('data.driver.full_name', 'Ramesh')
            ->assertJsonPath('data.driver.tanker.vehicle_number', 'MH12AB1234')
            ->assertJsonPath('data.today.assigned', 1)
            ->assertJsonPath('data.today.delivered', 1)
            ->assertJsonPath('data.today.earning', 150);
    }

    public function test_a_driver_sees_only_their_own_orders(): void
    {
        $other = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);

        $mine = $this->order(['status' => OrderStatusEnum::Assigned]);
        $this->order(['driver_id' => $other->id, 'status' => OrderStatusEnum::Assigned]);

        $response = $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.orders.index'))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->assertSame([$mine->id], array_column($response->json('data.items'), 'id'));
    }

    public function test_the_full_road_from_refill_to_delivered(): void
    {
        $order = $this->order(['status' => OrderStatusEnum::Assigned]);

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.refill', $order), ['spot_id' => $this->spot->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'refilling')
            ->assertJsonPath('data.spot.name', 'Hadapsar Borewell');

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.out-for-delivery', $order))
            ->assertOk()
            ->assertJsonPath('data.status', 'out_for_delivery');

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.delivered', $order))
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $order->refresh();

        $this->assertNotNull($order->refilled_at);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_the_driver_may_refill_at_a_different_spot_and_the_charge_follows(): void
    {
        $cheaper = Spot::query()->create(['name' => 'Wagholi Well', 'refill_charge' => 250]);

        $order = $this->order([
            'status' => OrderStatusEnum::Assigned,
            'spot_id' => $this->spot->id,
            'refill_charge' => 400,
        ]);

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.refill', $order), ['spot_id' => $cheaper->id])
            ->assertOk();

        $order->refresh();

        $this->assertSame($cheaper->id, $order->spot_id);
        $this->assertSame('250.00', $order->refill_charge);
    }

    public function test_an_unavailable_spot_is_refused_at_refill_time(): void
    {
        $this->spot->update(['is_available' => false]);
        $order = $this->order(['status' => OrderStatusEnum::Assigned]);

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.refill', $order), ['spot_id' => $this->spot->id])
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'That refill spot is unavailable. Choose another spot.');

        $this->assertSame(OrderStatusEnum::Assigned, $order->refresh()->status);
    }

    public function test_a_status_cannot_be_skipped_over_the_api(): void
    {
        $order = $this->order(['status' => OrderStatusEnum::Assigned]);

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.delivered', $order))
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertSame(OrderStatusEnum::Assigned, $order->refresh()->status);
    }

    public function test_a_driver_cannot_touch_another_drivers_order(): void
    {
        $other = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);
        $theirs = $this->order(['driver_id' => $other->id, 'status' => OrderStatusEnum::Assigned]);

        $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.orders.show', $theirs))
            ->assertForbidden();

        $this->actingAsApi($this->driver)
            ->postJson(route('api.driver.orders.delivered', $theirs))
            ->assertForbidden();
    }

    public function test_earnings_cover_the_range_and_today(): void
    {
        $this->order(['status' => OrderStatusEnum::Delivered, 'delivered_at' => now(), 'driver_earning' => 150]);
        $this->order(['status' => OrderStatusEnum::Delivered, 'delivered_at' => now(), 'driver_earning' => 150]);
        $this->order([
            'status' => OrderStatusEnum::Delivered,
            'delivered_at' => now()->subDays(10),
            'driver_earning' => 150,
        ]);

        $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.earnings', [
                'from' => now()->subDays(30)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.range.delivered', 3)
            ->assertJsonPath('data.range.earning', 450)
            ->assertJsonPath('data.today.delivered', 2)
            ->assertJsonPath('data.today.earning', 300);
    }

    public function test_nearest_spots_come_back_closest_first_with_a_distance(): void
    {
        Spot::query()->create([
            'name' => 'Far Spot', 'refill_charge' => 300, 'latitude' => 19.0760, 'longitude' => 72.8777,
        ]);

        $response = $this->actingAsApi($this->driver)
            ->getJson(route('api.spots.nearby', ['latitude' => 18.5204, 'longitude' => 73.8567]))
            ->assertOk();

        $names = array_column($response->json('data'), 'name');

        $this->assertSame('Hadapsar Borewell', $names[0]);
        $this->assertSame('Far Spot', $names[1]);
        $this->assertEquals(0, $response->json('data.0.distance_km'));
    }

    public function test_nearby_spots_need_coordinates(): void
    {
        $this->actingAsApi($this->driver)
            ->getJson(route('api.spots.nearby'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_a_driver_cannot_reach_customer_or_admin_endpoints(): void
    {
        $this->actingAsApi($this->driver)->getJson(route('api.customer.orders.index'))->assertForbidden();
        $this->actingAsApi($this->driver)->getJson(route('api.admin.orders.index'))->assertForbidden();
        $this->actingAsApi($this->driver)->getJson(route('api.admin.drivers.index'))->assertForbidden();
        $this->actingAsApi($this->driver)
            ->getJson(route('api.admin.reports.show', ['report' => 'driver-earning']))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = []): Order
    {
        return Order::query()->create([
            'order_no' => 'TNK-TEST-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $this->customer->id,
            'driver_id' => $this->driver->id,
            'tanker_id' => $this->tanker->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'order_amount' => 1200,
            'status' => OrderStatusEnum::Pending,
            ...$attributes,
        ]);
    }
}
