<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use App\Modules\Order\Models\Order;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * The owner's rule: a new order shows on the dashboard and leaves it the
 * moment it is delivered, while the Orders screen keeps every order there is.
 *
 * Both surfaces are tested here rather than in the API and panel suites
 * separately, because the thing that must not drift is that the app and the
 * panel answer the same question — they share one service and one scope.
 */
final class OpenOrdersTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_open_means_everything_but_delivered_and_cancelled(): void
    {
        // Derived from isOpen(), so this is what stops the scope and the
        // status chip drifting apart as statuses are added.
        $this->assertSame(
            ['pending', 'assigned', 'refilling', 'out_for_delivery'],
            OrderStatusEnum::openValues(),
        );
    }

    public function test_an_order_stays_on_the_dashboard_until_it_is_delivered(): void
    {
        $open = [
            $this->order(['status' => OrderStatusEnum::Pending]),
            $this->order(['status' => OrderStatusEnum::Assigned]),
            $this->order(['status' => OrderStatusEnum::Refilling]),
            $this->order(['status' => OrderStatusEnum::OutForDelivery]),
        ];

        $delivered = $this->order(['status' => OrderStatusEnum::Delivered]);
        $cancelled = $this->order(['status' => OrderStatusEnum::Cancelled]);

        $listed = app(DashboardServiceInterface::class)->openOrders();
        $ids = $listed['orders']->pluck('id')->all();

        foreach ($open as $order) {
            $this->assertContains($order->id, $ids, $order->status->label().' should be outstanding');
        }

        $this->assertNotContains($delivered->id, $ids);
        $this->assertNotContains($cancelled->id, $ids);
        $this->assertSame(4, $listed['total']);
    }

    public function test_delivering_an_order_drops_it_off_the_dashboard(): void
    {
        $order = $this->order(['status' => OrderStatusEnum::OutForDelivery]);

        $this->assertContains(
            $order->id,
            app(DashboardServiceInterface::class)->openOrders()['orders']->pluck('id')->all(),
        );

        $order->update(['status' => OrderStatusEnum::Delivered]);

        // Read again on the same request: the list is deliberately uncached,
        // so a delivery is reflected straight away rather than up to the
        // counters' one-minute TTL later.
        $after = app(DashboardServiceInterface::class)->openOrders();

        $this->assertNotContains($order->id, $after['orders']->pluck('id')->all());
        $this->assertSame(0, $after['total']);
    }

    public function test_the_list_is_newest_first_and_capped(): void
    {
        // Twelve outstanding, so the ten-row cap and its "of 12" line matter.
        $ids = [];

        for ($i = 0; $i < 12; $i++) {
            $ids[] = $this->order()->id;
        }

        $listed = app(DashboardServiceInterface::class)->openOrders();

        $this->assertCount(10, $listed['orders']);
        $this->assertSame(12, $listed['total']);
        $this->assertSame(array_slice(array_reverse($ids), 0, 10), $listed['orders']->pluck('id')->all());
    }

    public function test_the_api_dashboard_carries_the_open_orders(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9555500001']);
        $open = $this->order(['customer_id' => $customer->id]);
        $delivered = $this->order(['status' => OrderStatusEnum::Delivered]);

        $response = $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.dashboard'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'tankers', 'drivers', 'customers', 'orders_today', 'pending',
                'delivered_today', 'revenue_today', 'tankers_delivered_today',
                'open_total',
                'open_orders' => [['id', 'order_no', 'status', 'status_label', 'customer_name']],
            ]])
            ->assertJsonPath('data.open_total', 1);

        $ids = array_column($response->json('data.open_orders'), 'id');

        $this->assertSame([$open->id], $ids);
        $this->assertNotContains($delivered->id, $ids);
        // The row needs the customer's name, so the relation must be loaded —
        // a narrow select here is what left spots and drivers half-null.
        $this->assertSame('Test User', $response->json('data.open_orders.0.customer_name'));
    }

    public function test_the_panel_dashboard_lists_outstanding_orders_only(): void
    {
        $open = $this->order();
        $delivered = $this->order(['status' => OrderStatusEnum::Delivered]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Orders To Deliver')
            ->assertSee($open->order_no)
            ->assertDontSee($delivered->order_no);
    }

    public function test_the_panel_dashboard_says_so_when_nothing_is_outstanding(): void
    {
        $this->order(['status' => OrderStatusEnum::Delivered]);

        $this->actingAs($this->adminUser)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Nothing outstanding')
            ->assertSee('New orders appear here');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = []): Order
    {
        $customer = $attributes['customer_id'] ?? $this->userWithRole(
            RoleEnum::Customer,
            ['mobile' => (string) random_int(9000000000, 9999999999)],
        )->id;

        return Order::query()->create([
            'order_no' => 'TNK-TEST-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $customer,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'order_amount' => 1000,
            'status' => OrderStatusEnum::Pending,
            ...$attributes,
        ]);
    }
}
