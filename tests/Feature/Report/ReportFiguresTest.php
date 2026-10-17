<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Report\Reports\CustomerOrderReport;
use App\Modules\Report\Reports\DailyDeliveryReport;
use App\Modules\Report\Reports\DriverSalaryReport;
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
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'monthly_salary' => 15000,
        ]);
        $this->driverB = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Suresh', 'last_name' => '', 'mobile' => '9222222222', 'monthly_salary' => 20000,
        ]);
        $this->customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
        ]);
        $this->tanker = Tanker::query()->create([
            'vehicle_name' => 'Tanker One', 'vehicle_number' => 'MH12AB1234', 'capacity_litres' => 5000,
        ]);
        $this->spot = Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);
    }

    public function test_driver_salary_counts_deliveries_and_sums_the_snapshots(): void
    {
        // Ramesh: 3 delivered, one load each, ₹1000 each. Suresh: 1 at ₹900.
        $this->delivered($this->driverA, 3, amount: 1000);
        $this->delivered($this->driverB, 1, amount: 900);

        $rows = app(DriverSalaryReport::class)->rows($this->thisMonth());
        $byDriver = $rows->keyBy(0);

        // Columns: 3 delivered, 4 tankers, 5 order value, 7 monthly salary.
        $this->assertSame(3, $byDriver['Ramesh'][3]);
        $this->assertSame(3, $byDriver['Ramesh'][4]);
        $this->assertSame(3000.0, $byDriver['Ramesh'][5]);
        // The salary is the driver's monthly figure, untouched by the range.
        $this->assertSame(15000.0, $byDriver['Ramesh'][7]);

        $this->assertSame(1, $byDriver['Suresh'][3]);
        $this->assertSame(20000.0, $byDriver['Suresh'][7]);
    }

    public function test_driver_salary_ignores_orders_that_are_not_delivered(): void
    {
        $this->delivered($this->driverA, 2, amount: 1000);
        $this->order([
            'driver_id' => $this->driverA->id,
            'status' => OrderStatusEnum::OutForDelivery,
        ]);
        $this->order([
            'driver_id' => $this->driverA->id,
            'status' => OrderStatusEnum::Cancelled,
        ]);

        $row = app(DriverSalaryReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');

        $this->assertSame(2, $row[3]);
        $this->assertSame(2, $row[4]);
    }

    public function test_driver_salary_respects_the_date_range(): void
    {
        $this->delivered($this->driverA, 1, amount: 1000, deliveredAt: now()->subDays(40));
        $this->delivered($this->driverA, 2, amount: 1000);

        $thisMonth = app(DriverSalaryReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');
        $this->assertSame(2, $thisMonth[3]);

        $wide = app(DriverSalaryReport::class)->rows(new ReportFilters(
            from: now()->subDays(60)->toDateString(),
            to: now()->toDateString(),
        ))->firstWhere(0, 'Ramesh');
        $this->assertSame(3, $wide[3]);
    }

    public function test_driver_salary_can_be_filtered_to_one_driver(): void
    {
        $this->delivered($this->driverA, 2, amount: 1000);
        $this->delivered($this->driverB, 1, amount: 900);

        $rows = app(DriverSalaryReport::class)->rows(new ReportFilters(
            from: now()->startOfMonth()->toDateString(),
            to: now()->toDateString(),
            driverId: $this->driverA->id,
        ));

        $this->assertCount(1, $rows);
        $this->assertSame('Ramesh', $rows->first()[0]);
    }

    public function test_driver_salary_totals_add_up(): void
    {
        $this->delivered($this->driverA, 3, amount: 1000);
        $this->delivered($this->driverB, 1, amount: 900);

        $totals = app(DriverSalaryReport::class)->totals($this->thisMonth());

        $this->assertSame('4', $totals['Deliveries']);
        $this->assertSame('4', $totals['Tankers delivered']);
        $this->assertSame('₹ 3,900.00', $totals['Order value']);
        // Both salaries added, NOT prorated across the range — and the key says so.
        $this->assertSame('₹ 35,000.00', $totals['Salary per month (not for the range)']);
    }

    public function test_daily_delivery_lists_every_day_in_the_range(): void
    {
        $this->delivered($this->driverA, 2, amount: 1000);

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
        $this->delivered($this->driverA, 1, amount: 1000);
        $this->order(['status' => OrderStatusEnum::Cancelled]);

        $today = app(DailyDeliveryReport::class)->rows($this->thisMonth())->first();

        $this->assertSame(2, $today[1]);
        $this->assertSame(1, $today[2]);
        $this->assertSame(1, $today[3]);
    }

    public function test_spot_refill_counts_refills_and_cost(): void
    {
        $this->delivered($this->driverA, 3, amount: 1000);

        $row = app(SpotRefillReport::class)->rows($this->thisMonth())->firstWhere(0, 'Hadapsar Borewell');

        $this->assertSame(3, $row[3]);
        $this->assertSame(1200.0, $row[4]);
    }

    public function test_customer_order_sums_what_the_customer_paid(): void
    {
        $this->delivered($this->driverA, 2, amount: 1200);

        $row = app(CustomerOrderReport::class)->rows($this->thisMonth())->firstWhere(0, 'Sunita');

        $this->assertSame(2, $row[3]);
        $this->assertSame(2, $row[4]);
        $this->assertSame(2400.0, $row[8]);
    }

    public function test_tanker_utilisation_counts_deliveries_per_tanker(): void
    {
        $this->delivered($this->driverA, 4, amount: 1000);

        $row = app(TankerUtilisationReport::class)->rows($this->thisMonth())->firstWhere(1, 'MH12AB1234');

        $this->assertSame(4, $row[4]);
        // Four deliveries of one tanker each — the column counts tanker-loads
        // now, not litres.
        $this->assertSame(4, $row[5]);
        $this->assertSame(4000.0, $row[6]);
    }

    public function test_a_range_with_no_trade_reports_zeroes_not_an_error(): void
    {
        $filters = new ReportFilters(
            from: now()->subYear()->toDateString(),
            to: now()->subYear()->toDateString(),
        );

        $totals = app(DriverSalaryReport::class)->totals($filters);

        $this->assertSame('0', $totals['Deliveries']);
        $this->assertSame('0', $totals['Tankers delivered']);
        $this->assertSame('₹ 0.00', $totals['Order value']);
        // The salary is still owed in a month with no deliveries at all, so it
        // is the one total that is NOT zero here.
        $this->assertSame('₹ 35,000.00', $totals['Salary per month (not for the range)']);
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
        float $amount,
        ?\DateTimeInterface $deliveredAt = null,
    ): void {
        for ($i = 0; $i < $count; $i++) {
            $this->order([
                'driver_id' => $driver->id,
                'status' => OrderStatusEnum::Delivered,
                'order_amount' => $amount,
                'refill_charge' => 400,
                'tanker_count' => 1,
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
