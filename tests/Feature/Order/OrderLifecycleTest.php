<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class OrderLifecycleTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $customer;

    private User $driver;

    private Tanker $tanker;

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
            'mobile' => '9876543210', 'first_name' => 'Ramesh', 'per_delivery_rate' => 150,
        ]);
        $this->tanker = Tanker::query()->create([
            'vehicle_name' => 'Water Tanker 1', 'vehicle_number' => 'MH12AB1234', 'capacity_litres' => 5000,
        ]);
        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400,
            'latitude' => 18.5204, 'longitude' => 73.8567,
        ]);
    }

    public function test_placing_an_order_generates_a_reference_and_logs_the_opening_status(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.order.store'), $this->orderPayload())
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        // Derived from the row id, so the sequence is not guaranteed to start
        // at 1 — the format and uniqueness are what matter.
        $this->assertMatchesRegularExpression(
            '/^TNK-'.now()->format('Y').'-\d{5,}$/',
            (string) $order->order_no,
        );
        $this->assertSame(OrderStatusEnum::Pending, $order->status);
        $this->assertSame($this->adminUser->id, $order->created_by);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::Pending->value,
            'note' => 'Order placed',
        ]);
    }

    public function test_order_references_do_not_collide(): void
    {
        $this->actingAs($this->adminUser)->post(route('admin.order.store'), $this->orderPayload());
        $this->actingAs($this->adminUser)->post(route('admin.order.store'), $this->orderPayload());

        $this->assertSame(2, Order::query()->distinct()->count('order_no'));
    }

    public function test_an_order_cannot_be_placed_against_a_driver_id(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.order.store'), [...$this->orderPayload(), 'customer_id' => $this->driver->id])
            ->assertSessionHasErrors('customer_id');
    }

    public function test_assigning_snapshots_the_tanker_refill_charge_and_driver_rate(): void
    {
        $this->giveDriverATanker();
        $order = $this->placeOrder();

        $this->actingAs($this->adminUser)
            ->put(route('admin.order.assign.update', $order), [
                'driver_id' => $this->driver->id,
                'spot_id' => $this->spot->id,
            ])
            ->assertRedirect(route('admin.order.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatusEnum::Assigned, $order->status);
        $this->assertSame($this->driver->id, $order->driver_id);
        $this->assertSame($this->tanker->id, $order->tanker_id);
        $this->assertSame($this->spot->id, $order->spot_id);
        $this->assertSame('400.00', $order->refill_charge);
        $this->assertSame('150.00', $order->driver_earning);
        $this->assertNotNull($order->assigned_at);
    }

    public function test_later_rate_changes_do_not_rewrite_an_assigned_order(): void
    {
        $this->giveDriverATanker();
        $order = $this->placeOrder();

        $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);

        $this->driver->update(['per_delivery_rate' => 999]);
        $this->spot->update(['refill_charge' => 999]);

        $this->assertSame('150.00', $order->refresh()->driver_earning);
        $this->assertSame('400.00', $order->refresh()->refill_charge);
    }

    public function test_a_driver_without_a_tanker_cannot_be_assigned(): void
    {
        $order = $this->placeOrder();

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('no tanker assigned');

        $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
    }

    public function test_a_deactivated_driver_cannot_be_assigned(): void
    {
        $this->giveDriverATanker();
        $this->driver->update(['status' => false]);
        $order = $this->placeOrder();

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('deactivated');

        $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
    }

    public function test_an_unavailable_spot_is_refused(): void
    {
        $this->giveDriverATanker();
        $this->spot->update(['is_available' => false]);
        $order = $this->placeOrder();

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('unavailable');

        $this->service()->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
    }

    public function test_the_full_road_from_pending_to_delivered(): void
    {
        $this->giveDriverATanker();
        $order = $this->placeOrder();
        $service = $this->service();

        $service->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
        $service->moveTo($order, OrderStatusEnum::Refilling, $this->driver);
        $service->moveTo($order, OrderStatusEnum::OutForDelivery, $this->driver);
        $service->moveTo($order, OrderStatusEnum::Delivered, $this->driver);

        $order->refresh();

        $this->assertSame(OrderStatusEnum::Delivered, $order->status);
        $this->assertNotNull($order->refilled_at);
        $this->assertNotNull($order->delivered_at);

        $this->assertSame([
            'pending', 'assigned', 'refilling', 'out_for_delivery', 'delivered',
        ], $order->statusLogs->pluck('status')->map(fn ($s) => $s->value)->all());
    }

    public function test_a_status_cannot_be_skipped(): void
    {
        $this->giveDriverATanker();
        $order = $this->placeOrder();

        $this->expectException(InvalidStatusTransitionException::class);

        // Pending straight to delivered is not a road the order may take.
        $this->service()->moveTo($order, OrderStatusEnum::Delivered, $this->adminUser);
    }

    public function test_a_delivered_order_is_final(): void
    {
        $this->giveDriverATanker();
        $order = $this->placeOrder();
        $service = $this->service();

        $service->assign($order, $this->driver->id, $this->spot->id, $this->adminUser);
        $service->moveTo($order, OrderStatusEnum::Refilling, $this->driver);
        $service->moveTo($order, OrderStatusEnum::OutForDelivery, $this->driver);
        $service->moveTo($order, OrderStatusEnum::Delivered, $this->driver);

        $this->expectException(InvalidStatusTransitionException::class);

        $service->cancel($order, 'Changed my mind', $this->adminUser);
    }

    public function test_cancelling_records_the_reason(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->adminUser)
            ->put(route('admin.order.cancel', $order), ['cancel_reason' => 'Customer not reachable'])
            ->assertSessionHas('success');

        $order->refresh();

        $this->assertSame(OrderStatusEnum::Cancelled, $order->status);
        $this->assertSame('Customer not reachable', $order->cancel_reason);
    }

    public function test_cancelling_requires_a_reason(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->adminUser)
            ->put(route('admin.order.cancel', $order), [])
            ->assertSessionHasErrors('cancel_reason');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'quantity_litres' => 5000,
            'order_amount' => 1200,
        ];
    }

    private function placeOrder(): Order
    {
        return $this->service()->place($this->orderPayload(), $this->adminUser);
    }

    private function giveDriverATanker(): void
    {
        $this->tanker->assignments()->create([
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
