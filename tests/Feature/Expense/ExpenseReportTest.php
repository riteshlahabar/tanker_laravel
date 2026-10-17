<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Notification\Services\DailyReportNotifier;
use App\Modules\Report\Reports\DriverExpenseReport;
use App\Modules\Report\Reports\DriverSalaryReport;
use App\Modules\Report\Support\ReportFilters;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class ExpenseReportTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $ramesh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->adminUser = $this->admin();
        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'monthly_salary' => 15000,
        ]);
    }

    public function test_the_expense_report_splits_a_drivers_spending_by_category(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);
        $this->expense(ExpenseCategoryEnum::Fuel, 500);
        $this->expense(ExpenseCategoryEnum::Toll, 90);
        $this->expense(ExpenseCategoryEnum::Food, 150);

        $row = app(DriverExpenseReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');

        // Entries, Fuel, Toll, Maintenance, Food, Other, Total.
        $this->assertSame(4, $row[2]);
        $this->assertSame(2500.0, $row[3]);
        $this->assertSame(90.0, $row[4]);
        $this->assertSame(0.0, $row[5]);
        $this->assertSame(150.0, $row[6]);
        $this->assertSame(2740.0, $row[8]);
    }

    public function test_the_expense_report_respects_the_date_range(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);
        $this->expense(ExpenseCategoryEnum::Fuel, 777, now()->subDays(40));

        $thisMonth = app(DriverExpenseReport::class)->rows($this->thisMonth())->firstWhere(0, 'Ramesh');
        $this->assertSame(2000.0, $thisMonth[8]);

        $wide = app(DriverExpenseReport::class)->rows(new ReportFilters(
            from: now()->subDays(60)->toDateString(),
            to: now()->toDateString(),
        ))->firstWhere(0, 'Ramesh');
        $this->assertSame(2777.0, $wide[8]);
    }

    public function test_the_expense_report_totals_separate_fuel_from_the_rest(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);
        $this->expense(ExpenseCategoryEnum::Toll, 90);

        $totals = app(DriverExpenseReport::class)->totals($this->thisMonth());

        $this->assertSame('₹ 2,000.00', $totals['Fuel']);
        $this->assertSame('₹ 90.00', $totals['Everything else']);
        $this->assertSame('₹ 2,090.00', $totals['Total spent']);
    }

    public function test_the_expense_report_page_and_export_both_load(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);

        $this->actingAs($this->adminUser)
            ->get(route('admin.report.driver-expense'))
            ->assertOk()
            ->assertSee('Driver Expenses');

        $this->actingAs($this->adminUser)
            ->get(route('admin.report.export', ['report' => 'driver-expense']))
            ->assertOk();
    }

    public function test_the_salary_report_carries_expenses_without_moving_the_other_columns(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);

        $report = app(DriverSalaryReport::class);
        $row = $report->rows($this->thisMonth())->firstWhere(0, 'Ramesh');

        // The guard that matters: DailyReportMessage reads 0, 3 and 4 by
        // position, so Expenses must sit at the END of the row.
        $this->assertSame('Expenses', $report->headings()[8]);
        $this->assertSame('Monthly Salary', $report->headings()[7]);
        $this->assertSame('Delivered', $report->headings()[3]);
        $this->assertSame('Tankers', $report->headings()[4]);
        $this->assertSame(2000.0, $row[8]);
    }

    public function test_the_salary_report_keeps_the_monthly_and_range_totals_apart(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);

        $totals = app(DriverSalaryReport::class)->totals($this->thisMonth());

        // A salary is monthly; expenses belong to the range. The two keys say
        // so, because adding them together would be a figure that means nothing.
        $this->assertSame('₹ 15,000.00', $totals['Salary per month (not for the range)']);
        $this->assertSame('₹ 2,000.00', $totals['Expenses in the range']);
    }

    public function test_the_nightly_whatsapp_carries_one_expenses_line(): void
    {
        $this->expense(ExpenseCategoryEnum::Fuel, 2000);

        $body = app(DailyReportNotifier::class)->sendFor(now()->toDateString())['body'];

        // One line a day, not a message per petrol fill.
        $this->assertStringContainsString('Expenses today: ₹ 2,000.00', $body);
    }

    private function thisMonth(): ReportFilters
    {
        return new ReportFilters(
            from: now()->startOfMonth()->toDateString(),
            to: now()->toDateString(),
        );
    }

    private function expense(ExpenseCategoryEnum $category, float $amount, ?\DateTimeInterface $on = null): void
    {
        DriverExpense::query()->create([
            'driver_id' => $this->ramesh->id,
            'expense_date' => ($on ?? now())->format('Y-m-d'),
            'category' => $category->value,
            'amount' => $amount,
            'created_by' => $this->ramesh->id,
        ]);
    }
}
