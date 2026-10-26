<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Notification\Models\WhatsappMessage;
use App\Modules\Order\Contracts\OrderServiceInterface;
use App\Modules\Order\Exceptions\OrderAssignmentException;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * Handing an assigned order to a different driver — the absent-driver case,
 * and the only way a standing order's preferred driver can be overridden for
 * one day without cancelling the order.
 */
final class OrderReassignTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $customer;

    private User $ramesh;

    private User $suresh;

    private Tanker $firstTanker;

    private Tanker $secondTanker;

    private Spot $spot;

    private Spot $otherSpot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->adminUser = $this->admin();

        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'mobile' => '9123456780', 'first_name' => 'Sunita', 'address' => 'Kothrud, Pune',
        ]);

        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'mobile' => '9876543210', 'first_name' => 'Ramesh', 'monthly_salary' => 15000,
        ]);

        $this->suresh = $this->userWithRole(RoleEnum::Driver, [
            'mobile' => '9876500000', 'first_name' => 'Suresh', 'monthly_salary' => 18000,
        ]);

        $this->firstTanker = Tanker::query()->create([
            'vehicle_name' => 'Water Tanker 1', 'vehicle_number' => 'MH12AB1234', 'capacity_litres' => 5000,
        ]);

        $this->secondTanker = Tanker::query()->create([
            'vehicle_name' => 'Water Tanker 2', 'vehicle_number' => 'MH12CD5678', 'capacity_litres' => 6000,
        ]);

        $this->spot = Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400,
        ]);

        $this->otherSpot = Spot::query()->create([
            'name' => 'Wagholi Well', 'refill_charge' => 550,
        ]);
    }

    public function test_the_admin_can_hand_an_assigned_order_to_another_driver(): void
    {
        $order = $this->assignedOrder();
        $assignedAt = $order->assigned_at;

        $this->actingAs($this->adminUser)
            ->put(route('admin.order.reassign.update', $order), [
                'driver_id' => $this->suresh->id,
                'reason' => 'Ramesh absent',
            ])
            ->assertRedirect(route('admin.order.show', $order));

        $order->refresh();

        $this->assertSame($this->suresh->id, $order->driver_id);
        // The new driver's own tanker travels with them.
        $this->assertSame($this->secondTanker->id, $order->tanker_id);
        // No pay travels with the handover: a salary is monthly, so there is
        // nothing per-order to move from one driver to the other.
        $this->assertArrayNotHasKey('driver_earning', $order->getAttributes());
        // The status has not moved: it is still assigned, just to somebody else.
        $this->assertSame(OrderStatusEnum::Assigned, $order->status);
        // Re-stamping this would tell the reports the order waited less than it did.
        $this->assertEquals($assignedAt, $order->assigned_at);
    }

    public function test_the_handover_is_written_to_the_order_timeline(): void
    {
        $order = $this->assignedOrder();

        $this->service()->reassign($order, $this->suresh->id, null, $this->adminUser, 'Ramesh absent');

        // The relation orders by id ascending, so the newest entry is the last.
        $note = $order->statusLogs()->get()->last()?->note;

        $this->assertSame('Driver changed from Ramesh User to Suresh User — Ramesh absent', $note);
    }

    public function test_the_refill_spot_is_kept_unless_a_new_one_is_named(): void
    {
        $order = $this->assignedOrder();

        $this->service()->reassign($order, $this->suresh->id, null, $this->adminUser);

        $order->refresh();
        $this->assertSame($this->spot->id, $order->spot_id);
        $this->assertSame('400.00', $order->refill_charge);

        $this->service()->reassign($order, $this->ramesh->id, $this->otherSpot->id, $this->adminUser);

        $order->refresh();
        $this->assertSame($this->otherSpot->id, $order->spot_id);
        // Re-snapshotted, so the report charges what this order will actually cost.
        $this->assertSame('550.00', $order->refill_charge);
    }

    public function test_a_handover_is_refused_once_the_tanker_is_being_filled(): void
    {
        $order = $this->assignedOrder();
        $this->service()->moveTo($order, OrderStatusEnum::Refilling, $this->ramesh);

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('Only an order that is still waiting with its driver can be handed over');

        $this->service()->reassign($order->refresh(), $this->suresh->id, null, $this->adminUser);
    }

    public function test_an_order_with_no_driver_yet_is_sent_to_assign_instead(): void
    {
        $order = $this->placeOrder();

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('This order has no driver yet. Use Assign instead.');

        $this->service()->reassign($order, $this->suresh->id, null, $this->adminUser);
    }

    public function test_handing_the_order_to_the_driver_who_already_has_it_is_refused(): void
    {
        $order = $this->assignedOrder();

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('That driver already has this order.');

        $this->service()->reassign($order, $this->ramesh->id, null, $this->adminUser);
    }

    public function test_a_driver_with_no_tanker_cannot_be_given_the_order(): void
    {
        $order = $this->assignedOrder();

        $tankerless = $this->userWithRole(RoleEnum::Driver, [
            'mobile' => '9000000001', 'first_name' => 'Mahesh', 'monthly_salary' => 12000,
        ]);

        $this->expectException(OrderAssignmentException::class);
        $this->expectExceptionMessage('This driver has no tanker assigned.');

        $this->service()->reassign($order, $tankerless->id, null, $this->adminUser);
    }

    public function test_a_standing_order_day_changes_hands_without_touching_the_routine(): void
    {
        $schedule = DeliverySchedule::query()->create([
            'customer_id' => $this->customer->id,
            'interval_days' => 1,
            'tanker_count' => 1,
            'delivery_time' => '09:00',
            'delivery_address' => 'Kothrud, Pune',
            // The routine's usual driver — first preference for every day it makes.
            'preferred_driver_id' => $this->ramesh->id,
            'start_date' => now()->toDateString(),
            'next_run_date' => now()->toDateString(),
            'status' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $order = $this->assignedOrder(['delivery_schedule_id' => $schedule->id]);

        $this->service()->reassign($order, $this->suresh->id, null, $this->adminUser, 'Ramesh absent');

        $this->assertSame($this->suresh->id, $order->refresh()->driver_id);
        // Tomorrow's order goes back to Ramesh: one absence is not a permanent change.
        $this->assertSame($this->ramesh->id, $schedule->refresh()->preferred_driver_id);

        // The app has no other way to tell a routine delivery from an ad-hoc one,
        // and the change-driver sheet promises the routine is left alone.
        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson(route('api.admin.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.is_scheduled', true);

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson(route('api.admin.orders.show', $this->placeOrder()))
            ->assertOk()
            ->assertJsonPath('data.is_scheduled', false);
    }

    public function test_both_drivers_the_customer_and_the_owner_are_told(): void
    {
        config(['services.whatsapp.admin_number' => '9999900000']);

        $order = $this->assignedOrder();

        $this->service()->reassign($order, $this->suresh->id, null, $this->adminUser, 'Ramesh absent');

        $sent = WhatsappMessage::query()
            ->where('template', 'order.reassigned')
            ->pluck('to')
            ->all();

        // The driver who lost it most of all: they must stop expecting it.
        $this->assertContains('9876543210', $sent);
        $this->assertContains('9876500000', $sent);
        $this->assertContains('9123456780', $sent);
        $this->assertContains('9999900000', $sent);
    }

    public function test_the_api_hands_the_order_over_and_returns_the_new_driver(): void
    {
        $order = $this->assignedOrder();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson(route('api.admin.orders.reassign', $order), [
                'driver_id' => $this->suresh->id,
                'reason' => 'Ramesh absent',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.driver.id', $this->suresh->id);
    }

    public function test_a_driver_cannot_hand_their_own_order_to_somebody_else(): void
    {
        $order = $this->assignedOrder();

        $this->actingAs($this->ramesh, 'sanctum')
            ->postJson(route('api.admin.orders.reassign', $order), [
                'driver_id' => $this->suresh->id,
            ])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assignedOrder(array $attributes = []): Order
    {
        $this->firstTanker->assignments()->create([
            'driver_id' => $this->ramesh->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->secondTanker->assignments()->create([
            'driver_id' => $this->suresh->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $this->ramesh->refresh();
        $this->suresh->refresh();

        $order = $this->placeOrder($attributes);

        return $this->service()->assign($order, $this->ramesh->id, $this->spot->id, $this->adminUser);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function placeOrder(array $attributes = []): Order
    {
        return $this->service()->place([
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'tanker_count' => 1,
            'order_amount' => 1200,
            ...$attributes,
        ], $this->adminUser);
    }

    private function service(): OrderServiceInterface
    {
        return app(OrderServiceInterface::class);
    }
}
