<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPricing;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * Water is sold by the tanker-load, and the owner's tankers are not all priced
 * the same. The vehicle is unknown when an order is placed, so the order prices
 * at 0 and is priced from the vehicle at assignment — unless the rate was a
 * figure somebody gave rather than one read off the tanker. There is no global
 * fallback rate: the order carries `tanker_rate × tanker_count = order_amount`
 * and nothing else decides the price.
 */
final class TankerPricingTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $customer;

    private User $driver;

    private Tanker $bigTanker;

    private Tanker $unpricedTanker;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->adminUser = $this->admin();

        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'mobile' => '9123456780', 'first_name' => 'Sunita', 'address' => 'Kothrud, Pune',
        ]);

        $this->driver = $this->userWithRole(RoleEnum::Driver, [
            'mobile' => '9876543210', 'first_name' => 'Ramesh', 'monthly_salary' => 15000,
        ]);

        // A larger vehicle, priced above the other one.
        $this->bigTanker = Tanker::query()->create([
            'vehicle_name' => 'Big Tanker', 'vehicle_number' => 'MH12AB1234',
            'capacity_litres' => 10000, 'tanker_rate' => 1500,
        ]);

        // No rate of its own: anything it carries prices at 0.
        $this->unpricedTanker = Tanker::query()->create([
            'vehicle_name' => 'Spare Tanker', 'vehicle_number' => 'MH12CD5678',
            'capacity_litres' => 5000,
        ]);

        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400, 'address' => 'Hadapsar, Pune',
        ]);
    }

    public function test_the_price_comes_from_the_vehicle_and_nowhere_else(): void
    {
        $pricing = app(OrderPricing::class);

        $this->assertSame(3000.0, $pricing->amountFor(2, $this->bigTanker));

        // A vehicle with no rate, and no vehicle at all, both price at 0.
        // There is no global default to fall back on: a single figure would be
        // wrong for every tanker that has a rate of its own, and invented for
        // every one that does not.
        $this->assertSame(0.0, $pricing->amountFor(2, $this->unpricedTanker));
        $this->assertSame(0.0, $pricing->amountFor(2));
    }

    public function test_an_order_with_no_tanker_yet_prices_at_zero(): void
    {
        // Every app-placed order is in this state until an admin assigns a
        // driver — the price genuinely is not known before a tanker is chosen,
        // and 0 says so rather than quoting a figure the delivery may not match.
        $this->assertSame(0.0, app(OrderPricing::class)->amountFor(1));
        $this->assertSame(0.0, app(OrderPricing::class)->amountFor(3));
    }

    public function test_a_missing_count_is_treated_as_one_tanker(): void
    {
        $pricing = app(OrderPricing::class);

        $this->assertSame(1500.0, $pricing->amountFor(null, $this->bigTanker));
        $this->assertSame(1500.0, $pricing->amountFor(0, $this->bigTanker));
    }

    public function test_the_rate_and_the_total_are_stored_together(): void
    {
        // Two columns that must never disagree: the rate on an order is what
        // explains the total beside it when a customer asks how it was reached.
        $price = app(OrderPricing::class)->priceFrom($this->bigTanker, 2);

        $this->assertSame(['tanker_rate' => 1500.0, 'order_amount' => 3000.0], $price);
    }

    public function test_assignment_reprices_a_customer_order_from_the_tanker_that_goes_out(): void
    {
        $order = $this->customerOrder(tankers: 2);

        // Placed with no vehicle known, so there is no rate yet and no total.
        $this->assertSame('0.00', $order->tanker_rate);
        $this->assertSame('0.00', $order->order_amount);

        $this->giveDriver($this->bigTanker);
        $order = $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);

        // The big tanker went out, so 2 × 1500 — and the rate is recorded.
        $this->assertSame('1500.00', $order->refresh()->tanker_rate);
        $this->assertSame('3000.00', $order->refresh()->order_amount);
    }

    public function test_the_admin_form_prices_from_the_chosen_drivers_tanker(): void
    {
        $this->giveDriver($this->bigTanker);

        $this->actingAs($this->adminUser)->post(route('admin.order.store'), [
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'driver_id' => $this->driver->id,
            'spot_id' => $this->spot->id,
            'tanker_count' => 2,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();

        // The filling point named on the form is applied by the same assignment
        // the Assign screen uses, so its refill charge is snapshotted too.
        $this->assertSame($this->spot->id, $order->spot_id);
        $this->assertSame('400.00', $order->refill_charge);

        // 2 × 1500, straight off the vehicle. Nothing was typed, so the amount
        // stays calculated and a later handover may re-price it.
        $this->assertSame('1500.00', $order->tanker_rate);
        $this->assertSame('3000.00', $order->order_amount);
        $this->assertFalse((bool) $order->rate_fixed);
    }

    public function test_a_rate_the_admin_changed_on_the_form_is_the_figure_of_record(): void
    {
        $this->giveDriver($this->bigTanker);

        $this->actingAs($this->adminUser)->post(route('admin.order.store'), [
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'driver_id' => $this->driver->id,
            'tanker_count' => 2,
            // The vehicle charges 1500; this load is being sold at 1200.
            'tanker_rate' => 1200,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame('1200.00', $order->tanker_rate);
        $this->assertSame('2400.00', $order->order_amount);
        // Given rather than calculated, so a handover must not overwrite it.
        $this->assertTrue((bool) $order->rate_fixed);
    }

    public function test_a_handover_reprices_from_the_new_drivers_tanker(): void
    {
        $order = $this->customerOrder(tankers: 1);

        $this->giveDriver($this->bigTanker);
        $order = $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
        $this->assertSame('1500.00', $order->refresh()->order_amount);

        $replacement = $this->userWithRole(RoleEnum::Driver, [
            'mobile' => '9876500000', 'first_name' => 'Suresh', 'monthly_salary' => 18000,
        ]);

        $this->unpricedTanker->assignments()->create([
            'driver_id' => $replacement->id,
            'assigned_from' => now()->toDateString(),
        ]);
        $replacement->refresh();

        $this->service()->reassign($order, $replacement->id, null, $this->adminUser, 'Ramesh absent');

        // The spare tanker has no rate of its own, and there is nothing to fall
        // back on, so the order prices at 0 — which reads as "this vehicle has
        // no rate set" and sends the owner to its edit screen.
        $this->assertSame('0.00', $order->refresh()->tanker_rate);
        $this->assertSame('0.00', $order->refresh()->order_amount);
    }

    private function customerOrder(int $tankers): Order
    {
        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_address' => 'Kothrud, Pune',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => $tankers,
            ])
            ->assertCreated();

        return Order::query()->latest('id')->firstOrFail();
    }

    private function giveDriver(Tanker $tanker): void
    {
        $tanker->assignments()->create([
            'driver_id' => $this->driver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->driver->refresh();
    }

    private function service(): OrderServiceInterface
    {
        return app(OrderServiceInterface::class);
    }
}
