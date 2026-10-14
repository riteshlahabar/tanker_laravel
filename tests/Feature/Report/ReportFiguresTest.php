<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Report\Reports\CustomerOrderReport;
use App\Modules\Report\Reports\DailyDeliveryReport;
use App\Modules\Report\Reports\DriverEarningReport;
use App\Modules\Report\Reports\SpotRefillReport;
use App\Modules\Report\Reports\TankerUtilisationReport;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class ReportFiguresTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $driverA;

    private User $driverB;

    private User $customer;

    private Tanker $tanker;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->driverA = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'per_delivery_rate' => 150,
        ]);
        $this->driverB = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Suresh', 'last_name' => '', 'mobile' => '9222222222', 'per_delivery_rate' => 200,
        ]);
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
        ]);
        $this->tanker = Tanker::query()->create([
            'vehicle_name' => 'Tanker One', 'vehicle_number' => 'MH12AB1234', 'capacity_litres' => 5000,
        ]);
        $this->spot = Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);
    }

    public function test_driver_earning_counts_deliveries_and_sums_the_snapshots(): void
    {
        // Ramesh: 3 delivered at ₹150 each. Suresh: 1 delivered at ₹200.
        $this->delivered($this->driverA, 3, earning: 150, amount: 1000);
        $this->delivered($this->driverB, 1, earning: 200, amount: 900);

        $rows = app(DriverEarningReport::class)->rows($this->thisMonth());
        $byDriver = $rows->keyBy(0);

        $this->assertSame(3, $byDriver['Ramesh'][3]);
        $this->assertSame(450.0, $byDriver['Ramesh'][6]);
        $this->assertSame(3000.0, $byDriver['Ramesh'][4]);

        $this->assertSame(1, $byDriver['Suresh'][3]);
        $this->assertSame(200.0, $byDriver['Suresh'][6]);
    }

    public function test_driver_earning_ignores_orders_that_are_not_delivered(): void
    {
        $this->delivered($this->driverA, 2, earning: 150, amount: 1000);
        $this->order([
            'driver_id' => $this->driverA->id,
            'status' => OrderStatusEnum::OutForDelivery,
            'driver_earning' => 150,
        ]);
        $this->order([
            'driver_id' => $this->driverA->id,
            'status' => OrderStatusEnum::Cancelled,
            'driver_earning' => 150,
        ]);

        $row = app(DriverEarningReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');

        $this->assertSame(2, $row[3]);
        $this->assertSame(300.0, $row[6]);
    }

    public function test_driver_earning_respects_the_date_range(): void
    {
        $this->delivered($this->driverA, 1, earning: 150, amount: 1000, deliveredAt: now()->subDays(40));
        $this->delivered($this->driverA, 2, earning: 150, amount: 1000);

        $thisMonth = app(DriverEarningReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');
        $this->assertSame(2, $thisMonth[3]);

        $wide = app(DriverEarningReport::class)->rows(new ReportFilters(
            from: now()->subDays(60)->toDateString(),
            to: now()->toDateString(),
        ))->firstWhere(0, 'Ramesh');
        $this->assertSame(3, $wide[3]);
    }

    public function test_driver_earning_can_be_filtered_to_one_driver(): void
    {
        $this->delivered($this->driverA, 2, earning: 150, amount: 1000);
        $this->delivered($this->driverB, 1, earning: 200, amount: 900);

        $rows = app(DriverEarningReport::class)->rows(new ReportFilters(
            from: now()->startOfMonth()->toDateString(),
            to: now()->toDateString(),
            driverId: $this->driverA->id,
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('Ramesh', $rows->first()[0]);
    }

    public function test_driver_earning_totals_add_up(): void
    {
        $this->delivered($this->driverA, 3, earning: 150, amount: 1000);
        $this->delivered($this->driverB, 1, earning: 200, amount: 900);

        $totals = app(DriverEarningReport::class)->totals($this->thisMonth());

        $this->assertSame('4', $totals['Tankers delivered']);
        $this->assertSame('₹ 650.00', $totals['Total earning']);
        $this->assertSame('₹ 3,900.00', $totals['Order value']);
    }

    public function test_daily_delivery_lists_every_day_in_the_range(): void
    {
        $this->delivered($this->driverA, 2, earning: 150, amount: 1000);

        $rows = app(DailyDeliveryReport::class)->rows(new ReportFilters(
            from: now()->subDays(2)->toDateString(),
            to: now()->toDateString(),
        ));

        $this->assertCount(3, $rows);
        // Newest first, so today leads.
        $this->assertSame(2, $rows->first()[2]);
        $this->assertSame(2000.0, $rows->first()[4]);
        $this->assertSame(0, $rows->last()[2]);
    }

    public function test_daily_delivery_separates_cancelled_from_delivered(): void
    {
        $this->delivered($this->driverA, 1, earning: 150, amount: 1000);
        $this->order(['status' => OrderStatusEnum::Cancelled]);

        $today = app(DailyDeliveryReport::class)->rows($this->thisMonth())->first();

        $this->assertSame(2, $today[1]);
        $this->assertSame(1, $today[2]);
        $this->assertSame(1, $today[3]);
    }

    public function test_spot_refill_counts_refills_and_cost(): void
    {
        $this->delivered($this->driverA, 3, earning: 150, amount: 1000);

        $row = app(SpotRefillReport::class)->rows($this->thisMonth())->firstWhere(0, 'Hadapsar Borewell');

        $this->assertSame(3, $row[3]);
        $this->assertSame(1200.0, $row[4]);
    }

    public function test_customer_order_sums_what_the_customer_paid(): void
    {
        $this->delivered($this->driverA, 2, earning: 150, amount: 1200);

        $row = app(CustomerOrderReport::class)->rows($this->thisMonth())->firstWhere(0, 'Sunita');

        $this->assertSame(2, $row[3]);
        $this->assertSame(2, $row[4]);
        $this->assertSame(2400.0, $row[6]);
    }

    public function test_tanker_utilisation_counts_deliveries_per_tanker(): void
    {
        $this->delivered($this->driverA, 4, earning: 150, amount: 1000);

        $row = app(TankerUtilisationReport::class)->rows($this->thisMonth())->firstWhere(1, 'MH12AB1234');

        $this->assertSame(4, $row[4]);
        $this->assertSame(20000, $row[5]);
        $this->assertSame(4000.0, $row[6]);
    }

    public function test_a_range_with_no_trade_reports_zeroes_not_an_error(): void
    {
        $filters = new ReportFilters(
            from: now()->subYear()->toDateString(),
            to: now()->subYear()->toDateString(),
        );

        $this->assertSame('0', app(DriverEarningReport::class)->totals($filters)['Tankers delivered']);
        $this->assertSame('₹ 0.00', app(DriverEarningReport::class)->totals($filters)['Total earning']);
    }

    private function thisMonth(): ReportFilters
    {
        return new ReportFilters(
            from: now()->startOfMonth()->toDateString(),
            to: now()->toDateString(),
        );
    }

    private function delivered(
        User $driver,
        int $count,
        float $earning,
        float $amount,
        ?\DateTimeInterface $deliveredAt = null,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $this->order([
                'driver_id' => $driver->id,
                'status' => OrderStatusEnum::Delivered,
                'driver_earning' => $earning,
                'order_amount' => $amount,
                'refill_charge' => 400,
                'quantity_litres' => 5000,
                'delivered_at' => $deliveredAt ?? now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = []): Order
    {
        return Order::query()->create([
            'order_no' => 'TNK-TEST-'.str_pad((string) (Order::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'customer_id' => $this->customer->id,
            'tanker_id' => $this->tanker->id,
            'spot_id' => $this->spot->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'order_amount' => 1000,
            'status' => OrderStatusEnum::Pending,
            ...$attributes,
        ]);
    }

    public function test_daily_delivery_spreads_orders_placed_across_days(): void
    {
        // created_at is guarded, so it is set after the insert the way a real
        // order placed on an earlier day would already have it.
        $old = $this->order(['status' => OrderStatusEnum::Pending]);
        $old->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->order(['status' => OrderStatusEnum::Pending]);

        $rows = app(DailyDeliveryReport::class)->rows(new ReportFilters(
            from: now()->subDays(2)->toDateString(),
            to: now()->toDateString(),
        ));

        // Newest first: today has 1 placed, two days ago has 1, the middle none.
        $this->assertSame(1, $rows[0][1]);
        $this->assertSame(0, $rows[1][1]);
        $this->assertSame(1, $rows[2][1]);
    }
}
