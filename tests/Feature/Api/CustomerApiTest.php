<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Spot\Models\Spot;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class CustomerApiTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
            'address' => 'Kothrud, Pune',
        ]);
    }

    public function test_a_customer_can_place_an_order(): void
    {
        app(SettingRepositoryInterface::class)->put('default_order_amount', '1200');

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_address' => 'Kothrud, Pune',
                'delivery_date' => now()->toDateString(),
                'quantity_litres' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            // json_encode emits an integral float as 1200, not 1200.0 — the
            // Flutter model must read money as `num` and call toDouble().
            ->assertJsonPath('data.order_amount', 1200);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'order_amount' => 1200,
        ]);
    }

    public function test_a_booking_defaults_to_the_address_entered_when_the_customer_was_added(): void
    {
        $this->customer->update(['latitude' => 18.5074, 'longitude' => 73.8077]);

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_date' => now()->toDateString(),
                'quantity_litres' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.delivery_address', 'Kothrud, Pune')
            // Delivering to the registered address, so the driver gets its pin.
            ->assertJsonPath('data.latitude', 18.5074)
            ->assertJsonPath('data.longitude', 73.8077);
    }

    public function test_a_one_off_address_is_kept_and_does_not_borrow_the_customers_coordinates(): void
    {
        $this->customer->update(['latitude' => 18.5074, 'longitude' => 73.8077]);

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_address' => 'Site office, Wagholi',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.delivery_address', 'Site office, Wagholi')
            // The home pin would send the driver to the wrong place.
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.longitude', null);
    }

    public function test_the_order_is_always_placed_for_the_signed_in_customer(): void
    {
        $other = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);

        // customer_id in the body is ignored — it comes from the token.
        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'customer_id' => $other->id,
                'delivery_address' => 'Kothrud, Pune',
                'delivery_date' => now()->toDateString(),
            ])
            ->assertCreated();

        $this->assertSame($this->customer->id, Order::query()->firstOrFail()->customer_id);
    }

    public function test_per_litre_pricing_is_used_when_configured(): void
    {
        $settings = app(SettingRepositoryInterface::class);
        $settings->put('default_order_amount', '1200');
        $settings->put('price_per_litre', '0.25');

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_address' => 'Kothrud, Pune',
                'delivery_date' => now()->toDateString(),
                'quantity_litres' => 5000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_amount', 1250);
    }

    public function test_a_past_delivery_date_is_refused(): void
    {
        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.store'), [
                'delivery_address' => 'Kothrud, Pune',
                'delivery_date' => now()->subDay()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery_date');
    }

    public function test_a_customer_sees_only_their_own_orders(): void
    {
        $other = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);

        $mine = $this->order($this->customer);
        $theirs = $this->order($other);

        $response = $this->actingAsApi($this->customer)
            ->getJson(route('api.customer.orders.index'))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        $this->assertSame([$mine->id], array_column($response->json('data.items'), 'id'));
        $this->assertNotContains($theirs->id, array_column($response->json('data.items'), 'id'));
    }

    public function test_a_customer_cannot_read_another_customers_order(): void
    {
        $other = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);
        $theirs = $this->order($other);

        $this->actingAsApi($this->customer)
            ->getJson(route('api.customer.orders.show', $theirs))
            ->assertForbidden();
    }

    public function test_order_detail_carries_the_tracking_timeline(): void
    {
        $order = $this->order($this->customer);
        $order->statusLogs()->create(['status' => OrderStatusEnum::Pending, 'note' => 'Order placed']);

        $this->actingAsApi($this->customer)
            ->getJson(route('api.customer.orders.show', $order))
            ->assertOk()
            ->assertJsonPath('data.timeline.0.status', 'pending')
            ->assertJsonPath('data.timeline.0.label', 'Pending');
    }

    public function test_a_customer_can_cancel_a_pending_order(): void
    {
        $order = $this->order($this->customer);

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.cancel', $order), ['cancel_reason' => 'No longer needed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_a_customer_cannot_cancel_an_order_already_on_the_road(): void
    {
        $order = $this->order($this->customer, ['status' => OrderStatusEnum::OutForDelivery]);

        $this->actingAsApi($this->customer)
            ->postJson(route('api.customer.orders.cancel', $order), ['cancel_reason' => 'Changed my mind'])
            ->assertForbidden();
    }

    public function test_a_customer_cannot_reach_driver_or_admin_endpoints(): void
    {
        $this->actingAsApi($this->customer)->getJson(route('api.driver.orders.index'))->assertForbidden();
        $this->actingAsApi($this->customer)->getJson(route('api.admin.orders.index'))->assertForbidden();
        $this->actingAsApi($this->customer)->getJson(route('api.admin.dashboard'))->assertForbidden();
        $this->actingAsApi($this->customer)->getJson(route('api.admin.tankers.index'))->assertForbidden();
    }

    public function test_a_customer_can_read_the_usable_spots(): void
    {
        Spot::query()->create(['name' => 'Open Spot', 'refill_charge' => 400]);
        Spot::query()->create(['name' => 'Closed Spot', 'refill_charge' => 400, 'is_available' => false]);

        $this->actingAsApi($this->customer)
            ->getJson(route('api.spots.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Open Spot');
    }

    public function test_a_customer_can_update_their_own_profile(): void
    {
        $this->actingAsApi($this->customer)
            ->putJson(route('api.auth.profile.update'), [
                'first_name' => 'Sunita',
                'last_name' => 'Joshi',
                'address' => 'New Address, Pune',
            ])
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Sunita Joshi');

        $this->assertSame('New Address, Pune', $this->customer->refresh()->address);
    }

    public function test_the_profile_endpoint_cannot_change_the_mobile_or_rate(): void
    {
        $this->actingAsApi($this->customer)
            ->putJson(route('api.auth.profile.update'), [
                'first_name' => 'Sunita',
                'mobile' => '9999999999',
                'per_delivery_rate' => 9999,
            ])
            ->assertOk();

        $this->customer->refresh();

        $this->assertSame('9333333333', $this->customer->mobile);
        $this->assertSame('0.00', $this->customer->per_delivery_rate);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(User $customer, array $attributes = []): Order
    {
        return Order::query()->create([
            'order_no' => 'TNK-TEST-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'order_amount' => 1200,
            'status' => OrderStatusEnum::Pending,
            ...$attributes,
        ]);
    }
}
