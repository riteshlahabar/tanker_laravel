<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\OrderAlertEnum;
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
 * Phase 1 of the buzzer: the server's answer to "what has nobody looked at".
 *
 * Nothing rings yet. What is proved here is the fact every later phase is a
 * client of — who an order is *for*, that it stays unseen until the audience
 * says otherwise, and that nobody else can say it for them.
 */
final class OrderAlertsTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $owner;

    private User $ramesh;

    private User $suresh;

    private User $sunita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->owner = $this->userWithRole(RoleEnum::Admin, [
            'first_name' => 'Owner', 'last_name' => '', 'email' => 'owner@example.com',
        ]);
        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => 'Patil', 'mobile' => '9111111111',
            'monthly_salary' => 15000,
        ]);
        $this->suresh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Suresh', 'last_name' => 'Kale', 'mobile' => '9222222222',
            'monthly_salary' => 15000,
        ]);
        $this->sunita = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
            'address' => 'Flat 4, Kothrud',
        ]);

        foreach ([$this->ramesh, $this->suresh] as $index => $driver) {
            $tanker = Tanker::query()->create([
                'vehicle_name' => 'Tanker '.($index + 1),
                'vehicle_number' => 'MH12AB000'.($index + 1),
                'capacity_litres' => 5000,
                'tanker_rate' => 1500,
            ]);
            $this->giveTanker($driver, $tanker);
        }

        Spot::query()->create([
            'name' => 'Hadapsar Borewell', 'refill_charge' => 400, 'address' => 'Hadapsar',
        ]);
    }

    private function giveTanker(User $driver, Tanker $tanker): void
    {
        $tanker->assignments()->create([
            'driver_id' => $driver->id,
            'assigned_from' => now()->toDateString(),
        ]);

        $driver->refresh();
    }

    private function adminPlacesOrderFor(User $driver): Order
    {
        $id = $this->actingAsApi($this->owner)
            ->postJson(route('api.admin.orders.store'), [
                'customer_id' => $this->sunita->id,
                'driver_id' => $driver->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 1,
            ])
            ->assertCreated()
            ->json('data.id');

        return Order::query()->findOrFail($id);
    }

    private function driverPlacesOrder(User $driver): Order
    {
        $id = $this->actingAsApi($driver)
            ->postJson(route('api.driver.orders.store'), [
                'customer_id' => $this->sunita->id,
                'delivery_address' => 'Flat 4, Kothrud',
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 1,
            ])
            ->assertCreated()
            ->json('data.id');

        return Order::query()->findOrFail($id);
    }

    public function test_an_order_the_admin_places_rings_the_driver_it_was_given_to(): void
    {
        $order = $this->adminPlacesOrderFor($this->ramesh);

        $this->assertSame(OrderAlertEnum::Driver, $order->alert_for);
        $this->assertNull($order->alert_seen_at);

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.id', $order->id);

        // Not the other driver's phone, and not the office that wrote it.
        $this->actingAsApi($this->suresh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);

        $this->actingAsApi($this->owner)
            ->getJson(route('api.admin.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_an_order_a_driver_places_rings_the_office(): void
    {
        $order = $this->driverPlacesOrder($this->ramesh);

        $this->assertSame(OrderAlertEnum::Admin, $order->alert_for);

        $this->actingAsApi($this->owner)
            ->getJson(route('api.admin.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.id', $order->id);

        // The driver who wrote it is not rung about their own work.
        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_a_customers_own_order_rings_nobody(): void
    {
        $this->actingAsApi($this->sunita)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_date' => now()->toDateString(),
                'tanker_count' => 1,
            ])
            ->assertCreated();

        $this->assertNull(Order::query()->latest('id')->first()->alert_for);

        $this->actingAsApi($this->owner)
            ->getJson(route('api.admin.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_opening_the_order_stops_it_and_a_second_call_is_harmless(): void
    {
        $order = $this->adminPlacesOrderFor($this->ramesh);

        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.seen', $order))
            ->assertOk();

        $seenAt = $order->refresh()->alert_seen_at;
        $this->assertNotNull($seenAt);

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);

        // The Stop button can land after the order was opened; the first
        // timestamp is the one kept, because that is when it was seen.
        $this->travel(5)->minutes();

        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.orders.seen', $order))
            ->assertOk();

        $this->assertTrue($seenAt->equalTo($order->refresh()->alert_seen_at));
    }

    public function test_nobody_but_the_audience_can_silence_an_order(): void
    {
        $order = $this->adminPlacesOrderFor($this->ramesh);

        // The other driver cannot even see it, let alone silence it.
        $this->actingAsApi($this->suresh)
            ->postJson(route('api.driver.orders.seen', $order))
            ->assertForbidden();

        // And neither can the office that wrote it: an admin reads every
        // order, but silencing this one would lose the record of whether the
        // driver ever looked at the job.
        $this->actingAsApi($this->owner)
            ->postJson(route('api.admin.orders.seen', $order))
            ->assertForbidden();

        $this->assertNull($order->refresh()->alert_seen_at);
    }

    public function test_a_cancelled_or_delivered_order_stops_ringing_on_its_own(): void
    {
        $order = $this->adminPlacesOrderFor($this->ramesh);

        $this->actingAsApi($this->owner)
            ->postJson(route('api.admin.orders.cancel', $order), ['cancel_reason' => 'Customer called off'])
            ->assertOk();

        // Never marked seen — it simply stopped being worth ringing about,
        // which is the query doing the work rather than a second write.
        $this->assertNull($order->refresh()->alert_seen_at);
        $this->assertSame(OrderStatusEnum::Cancelled, $order->status);

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);
    }

    public function test_reassigning_moves_the_ringing_to_the_new_driver(): void
    {
        $order = $this->adminPlacesOrderFor($this->ramesh);

        $this->actingAsApi($this->owner)
            ->postJson(route('api.admin.orders.reassign', $order), ['driver_id' => $this->suresh->id])
            ->assertOk();

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 0);

        $this->actingAsApi($this->suresh)
            ->getJson(route('api.driver.orders.alerts'))
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.items.0.id', $order->id);
    }
}
