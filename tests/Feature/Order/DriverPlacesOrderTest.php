<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

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

/**
 * A driver taking an order on the route — `POST /api/driver/orders`.
 *
 * `ApiActingAs` signs with a real Sanctum token carrying the role's real
 * abilities, so these also prove the `order.create` grant in
 * `PermissionEnum::forRole()` actually reaches a driver's token.
 */
final class DriverPlacesOrderTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $ramesh;

    private User $sunita;

    private Tanker $tanker;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => 'Patil', 'mobile' => '9111111111',
            'monthly_salary' => 15000,
        ]);
        $this->sunita = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
            'address' => 'Flat 4, Kothrud',
        ]);

        $this->tanker = Tanker::query()->create([
            'vehicle_name' => 'Tanker One', 'vehicle_number' => 'MH12AB1234',
            'capacity_litres' => 5000, 'tanker_rate' => 1500,
        ]);
        $this->giveTanker($this->ramesh, $this->tanker);

        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400, 'address' => 'Hadapsar',
        ]);
    }

    public function test_a_driver_places_an_order_and_it_lands_on_them_assigned_and_priced(): void
    {
        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.driver.id', $this->ramesh->id)
            ->assertJsonPath('data.tanker_number', 'MH12AB1234')
            // 1,500 on the vehicle × 2 loads. Priced from the tanker they are
            // actually driving, not from anything the app sent.
            ->assertJsonPath('data.tanker_rate', 1500)
            ->assertJsonPath('data.order_amount', 3000);

        $order = Order::query()->findOrFail($response->json('data.id'));

        $this->assertSame($this->ramesh->id, $order->driver_id);
        $this->assertSame($this->tanker->id, $order->tanker_id);
        // The placer is recorded, which is what makes a driver-written order
        // auditable without any new column or screen.
        $this->assertSame($this->ramesh->id, $order->created_by);
        $this->assertFalse($order->rate_fixed);
    }

    /**
     * The security property of the whole feature.
     *
     * `DriverPlaceOrderRequest` has no `driver_id` rule, so the field is never
     * validated and never read — the order goes to the token's driver whatever
     * the body claims. If that rule were ever added back, this fails.
     */
    public function test_a_driver_cannot_place_work_onto_another_driver(): void
    {
        $suresh = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);
        $theirs = Tanker::query()->create([
            'vehicle_name' => 'Tanker Two', 'vehicle_number' => 'MH12CD5678', 'tanker_rate' => 9000,
        ]);
        $this->giveTanker($suresh, $theirs);

        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'driver_id' => $suresh->id,
                'tanker_count' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.driver.id', $this->ramesh->id);

        $order = Order::query()->findOrFail($response->json('data.id'));

        $this->assertSame($this->ramesh->id, $order->driver_id);
        $this->assertSame($this->tanker->id, $order->tanker_id);
        // And the price came from Ramesh's vehicle, not the 9,000 one he named.
        $this->assertSame('1500.00', $order->tanker_rate);
    }

    public function test_a_driver_with_no_tanker_is_refused_rather_than_left_with_a_priceless_order(): void
    {
        $spare = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9444444444']);

        $this->actingAsApi($spare)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_tanker_with_no_rate_prices_at_zero_rather_than_refusing(): void
    {
        $this->tanker->update(['tanker_rate' => null]);

        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 3,
            ])
            ->assertCreated()
            // 0 reads as "pricing is not configured on this vehicle" — the app
            // writes "Not priced yet" for it. Still a real order.
            ->assertJsonPath('data.order_amount', 0);
    }

    public function test_a_typed_rate_is_the_figure_of_record_and_survives_a_handover(): void
    {
        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 2,
                'tanker_rate' => 1200,
            ])
            ->assertCreated()
            ->assertJsonPath('data.tanker_rate', 1200)
            ->assertJsonPath('data.order_amount', 2400);

        $order = Order::query()->findOrFail($response->json('data.id'));

        $this->assertTrue($order->rate_fixed);
    }

    public function test_the_vehicles_own_rate_typed_back_is_not_an_override(): void
    {
        // The app pre-fills the field from the tanker, so the commonest submit
        // sends a rate equal to the vehicle's. That must not latch rate_fixed,
        // or every driver-placed order would silently refuse re-pricing.
        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_rate' => 1500,
            ])
            ->assertCreated();

        $this->assertFalse(Order::query()->findOrFail($response->json('data.id'))->rate_fixed);
    }

    public function test_the_filling_point_is_optional_and_snapshots_its_charge_when_given(): void
    {
        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'spot_id' => $this->spot->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.spot_id', $this->spot->id);

        $this->assertSame('400.00', Order::query()
            ->findOrFail($response->json('data.id'))
            ->refill_charge);
    }

    public function test_a_blank_address_falls_back_to_the_customers_registered_one(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => '',
                'delivery_date' => now()->toDateString(),
            ])
            // `delivery_address` is `required`, so an empty string is refused
            // here rather than quietly resolved — the app prefills it from the
            // chosen customer instead.
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_address');
    }

    public function test_the_customer_field_will_not_take_a_driver_or_an_admin(): void
    {
        foreach ([$this->ramesh->id, $this->admin()->id] as $notACustomer) {
            $this->actingAsApi($this->ramesh)
                ->postJson(route('api.driver.orders.store'), [
                    'customer_id' => $notACustomer,
                    'delivery_address' => 'Flat 4, Kothrud',
                    'delivery_date' => now()->toDateString(),
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('customer_id');
        }
    }

    public function test_a_date_in_the_past_is_refused(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->subDay()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_date');
    }

    /**
     * The claim that driver-placed orders are auditable with no new work: the
     * timeline's first entry already names who wrote it.
     */
    public function test_the_timeline_names_the_driver_who_placed_it(): void
    {
        $response = $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertCreated();

        $timeline = collect($response->json('data.timeline'));

        $this->assertSame('Ramesh Patil', $timeline->firstWhere('status', 'pending')['by']);
        $this->assertSame('Ramesh Patil', $timeline->firstWhere('status', 'assigned')['by']);
    }

    /**
     * Granting `order.create` makes `StoreOrderRequest::authorize()` and
     * `OrderPolicy::create()` pass for a driver. Neither is reachable, because
     * the admin group carries `role:admin` — but that is an inherited
     * guarantee rather than a stated one, so it is stated here.
     */
    public function test_the_grant_does_not_open_the_admins_order_endpoint(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.admin.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'driver_id' => $this->ramesh->id,
            ])
            ->assertForbidden();

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.admin.orders.index'))
            ->assertForbidden();
    }

    public function test_a_customer_cannot_reach_the_drivers_place_endpoint(): void
    {
        $this->actingAsApi($this->sunita)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_an_order_placed_by_a_driver_reads_back_as_theirs(): void
    {
        // The repository scope, not the controller, is what makes it visible —
        // so a placed order appears in their own list immediately.
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertCreated();

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.index'))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.status', OrderStatusEnum::Assigned->value);
    }

    private function giveTanker(User $driver, Tanker $tanker): void
    {
        $tanker->assignments()->create([
            'driver_id' => $driver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $driver->refresh();
    }
}
