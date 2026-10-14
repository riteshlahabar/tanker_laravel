<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class OrderVisibilityTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_a_driver_only_sees_their_own_orders(): void
    {
        $mine = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $theirs = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);

        $myOrder = $this->order(['driver_id' => $mine->id]);
        $theirOrder = $this->order(['driver_id' => $theirs->id]);

        $visible = app(OrderRepositoryInterface::class)->visibleTo($mine)->pluck('id')->all();

        $this->assertSame([$myOrder->id], $visible);
        $this->assertNotContains($theirOrder->id, $visible);
    }

    public function test_a_customer_only_sees_their_own_orders(): void
    {
        $mine = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);
        $theirs = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);

        $myOrder = $this->order(['customer_id' => $mine->id]);
        $this->order(['customer_id' => $theirs->id]);

        $this->assertSame(
            [$myOrder->id],
            app(OrderRepositoryInterface::class)->visibleTo($mine)->pluck('id')->all(),
        );
    }

    public function test_an_admin_sees_every_order(): void
    {
        $this->order();
        $this->order();

        $this->assertSame(2, app(OrderRepositoryInterface::class)->visibleTo($this->adminUser)->count());
    }

    public function test_a_driver_cannot_view_another_drivers_order(): void
    {
        $mine = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $theirs = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);
        $theirOrder = $this->order(['driver_id' => $theirs->id]);

        $this->assertFalse($mine->can('view', $theirOrder));
        $this->assertTrue($theirs->can('view', $theirOrder));
    }

    public function test_a_driver_cannot_move_another_drivers_order(): void
    {
        $mine = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $theirs = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9222222222']);

        $theirOrder = $this->order([
            'driver_id' => $theirs->id,
            'status' => OrderStatusEnum::Assigned,
        ]);

        $this->assertFalse($mine->can('updateStatus', $theirOrder));
        $this->assertTrue($theirs->can('updateStatus', $theirOrder));
    }

    public function test_a_driver_cannot_assign_orders(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $order = $this->order(['driver_id' => $driver->id]);

        $this->assertFalse($driver->can('assign', $order));
        $this->assertTrue($this->adminUser->can('assign', $order));
    }

    public function test_a_customer_may_only_call_off_an_order_nobody_has_started(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $pending = $this->order(['customer_id' => $customer->id, 'status' => OrderStatusEnum::Pending]);
        $assigned = $this->order(['customer_id' => $customer->id, 'status' => OrderStatusEnum::Assigned]);

        $this->assertTrue($customer->can('cancel', $pending));
        $this->assertFalse($customer->can('cancel', $assigned));
    }

    public function test_a_customer_cannot_cancel_someone_elses_pending_order(): void
    {
        $mine = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);
        $theirs = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9444444444']);

        $theirOrder = $this->order(['customer_id' => $theirs->id, 'status' => OrderStatusEnum::Pending]);

        $this->assertFalse($mine->can('cancel', $theirOrder));
    }

    public function test_a_driver_cannot_reach_the_admin_order_pages(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $order = $this->order(['driver_id' => $driver->id]);

        $this->actingAs($driver)->get(route('admin.order.index'))->assertForbidden();
        $this->actingAs($driver)->get(route('admin.order.show', $order))->assertForbidden();
    }

    public function test_the_orders_table_lists_rows_for_an_admin(): void
    {
        $this->order(['order_amount' => 1200]);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.order.index').'?draw=1&start=0&length=10')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('Unassigned');
    }

    public function test_the_status_filter_narrows_the_table(): void
    {
        $this->order(['status' => OrderStatusEnum::Pending]);
        $this->order(['status' => OrderStatusEnum::Delivered]);

        $this->actingAs($this->adminUser)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('admin.order.index').'?draw=1&start=0&length=10&status=delivered')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_the_export_downloads(): void
    {
        $this->order();

        $this->actingAs($this->adminUser)->get(route('admin.order.export'))->assertOk();
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
