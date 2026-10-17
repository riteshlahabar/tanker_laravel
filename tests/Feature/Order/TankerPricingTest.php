<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPricing;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * Water is sold by the tanker-load, and the owner's tankers are not all priced
 * the same. The vehicle is unknown when an order is placed, so the amount is
 * calculated from the setting and re-calculated from the vehicle at assignment
 * — unless it was a figure somebody gave rather than one we worked out.
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

        // A larger vehicle, priced above the default.
        $this->bigTanker = Tanker::query()->create([
            'vehicle_name' => 'Big Tanker', 'vehicle_number' => 'MH12AB1234',
            'capacity_litres' => 10000, 'tanker_rate' => 1500,
        ]);

        // No rate of its own: it must fall through to the setting.
        $this->unpricedTanker = Tanker::query()->create([
            'vehicle_name' => 'Spare Tanker', 'vehicle_number' => 'MH12CD5678',
            'capacity_litres' => 5000,
        ]);

        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400,
            'latitude' => 18.5204, 'longitude' => 73.8567,
        ]);

        $this->settings()->put('price_per_tanker', '900');
        $this->settings()->put('default_order_amount', '1200');
    }

    public function test_the_vehicles_own_rate_beats_the_configured_price(): void
    {
        $pricing = app(OrderPricing::class);

        $this->assertSame(3000.0, $pricing->amountFor(2, $this->bigTanker));
        // No rate on the vehicle, so the setting applies.
        $this->assertSame(1800.0, $pricing->amountFor(2, $this->unpricedTanker));
        // No vehicle at all — which is every order at the moment it is placed.
        $this->assertSame(1800.0, $pricing->amountFor(2));
    }

    public function test_a_zero_rate_means_the_flat_amount_and_is_not_multiplied(): void
    {
        $this->settings()->put('price_per_tanker', '0');

        // Three tankers, but the flat amount is a price per delivery.
        $this->assertSame(1200.0, app(OrderPricing::class)->amountFor(3));
    }

    public function test_a_missing_count_is_treated_as_one_tanker(): void
    {
        $pricing = app(OrderPricing::class);

        $this->assertSame(900.0, $pricing->amountFor(null));
        $this->assertSame(900.0, $pricing->amountFor(0));
    }

    public function test_assignment_reprices_a_customer_order_from_the_tanker_that_goes_out(): void
    {
        $order = $this->customerOrder(tankers: 2);

        // Placed with no vehicle known: 2 × the 900 setting.
        $this->assertSame('1800.00', $order->order_amount);

        $this->giveDriver($this->bigTanker);
        $order = $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);

        // The big tanker went out, so 2 × 1500.
        $this->assertSame('3000.00', $order->refresh()->order_amount);
    }

    public function test_an_amount_the_admin_typed_survives_assignment(): void
    {
        $this->actingAs($this->adminUser)->post(route('admin.order.store'), [
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'tanker_count' => 2,
            'order_amount' => 500,
        ])->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertTrue($order->amount_fixed);

        $this->giveDriver($this->bigTanker);
        $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);

        // Not 2 × 1500: the owner's own figure is the figure of record.
        $this->assertSame('500.00', $order->refresh()->order_amount);
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

        // The spare tanker has no rate, so the 900 setting applies instead.
        $this->assertSame('900.00', $order->refresh()->order_amount);
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

    private function settings(): SettingRepositoryInterface
    {
        return app(SettingRepositoryInterface::class);
    }

    private function service(): OrderServiceInterface
    {
        return app(OrderServiceInterface::class);
    }
}
