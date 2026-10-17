<?php

declare(strict_types=1);

namespace Tests\Feature\Report;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Report\Contracts\ReportRegistryInterface;
use App\Modules\Report\Reports\DriverSalaryReport;
use App\Modules\Report\Support\ReportFilters;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class ReportPageTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private const REPORTS = [
        'driver-salary', 'daily-delivery', 'spot-refill', 'customer-order', 'tanker-utilisation',
        'schedule-adherence', 'driver-expense',
    ];

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_every_report_page_loads(): void
    {
        $this->seedOneDelivery();

        foreach (self::REPORTS as $key) {
            $this->actingAs($this->adminUser)
                ->get(route("admin.report.{$key}"))
                ->assertOk()
                ->assertSee('Export Excel');
        }
    }

    public function test_every_report_exports(): void
    {
        $this->seedOneDelivery();

        foreach (self::REPORTS as $key) {
            $response = $this->actingAs($this->adminUser)
                ->get(route('admin.report.export', ['report' => $key]));

            $response->assertOk();
            $this->assertStringContainsString(
                'spreadsheet',
                (string) $response->headers->get('content-type'),
                "Export for [{$key}] did not return a spreadsheet.",
            );
        }
    }

    public function test_an_unknown_report_key_is_not_found(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.report.export', ['report' => 'made-up']))
            ->assertNotFound();
    }

    public function test_the_registry_exposes_exactly_the_seven_reports(): void
    {
        $this->assertSame(self::REPORTS, array_keys(app(ReportRegistryInterface::class)->all()));
    }

    public function test_the_date_range_defaults_to_this_month(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.report.driver-salary'))
            ->assertOk()
            ->assertSee(now()->startOfMonth()->toDateString())
            ->assertSee(now()->toDateString());
    }

    public function test_a_driver_filter_narrows_the_rows(): void
    {
        [$driver] = $this->seedOneDelivery();
        $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Nobody', 'last_name' => '', 'mobile' => '9777777777',
        ]);

        // Unfiltered, both drivers are listed; the filter leaves one. The name
        // still appears in the filter dropdown either way, so the "Drivers"
        // total is what proves the rows were narrowed.
        $unfiltered = app(DriverSalaryReport::class)
            ->rows(ReportFilters::forDay(now()->toDateString()));
        $this->assertCount(2, $unfiltered);

        $this->actingAs($this->adminUser)
            ->get(route('admin.report.driver-salary', ['driver_id' => $driver->id]))
            ->assertOk()
            ->assertSee('Ramesh')
            ->assertSeeInOrder(['Drivers', '>1<'], false);
    }

    public function test_the_export_link_carries_the_active_filters(): void
    {
        [$driver] = $this->seedOneDelivery();

        $this->actingAs($this->adminUser)
            ->get(route('admin.report.driver-salary', [
                'from' => '2026-01-01', 'to' => '2026-01-31', 'driver_id' => $driver->id,
            ]))
            ->assertOk()
            ->assertSee('from=2026-01-01', false)
            ->assertSee('driver_id='.$driver->id, false);
    }

    public function test_a_driver_cannot_reach_the_reports(): void
    {
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9888888888']);

        foreach (self::REPORTS as $key) {
            $this->actingAs($driver)->get(route("admin.report.{$key}"))->assertForbidden();
        }

        $this->actingAs($driver)
            ->get(route('admin.report.export', ['report' => 'driver-salary']))
            ->assertForbidden();
    }

    public function test_reports_require_a_login(): void
    {
        $this->get(route('admin.report.driver-salary'))->assertRedirect(route('login'));
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function seedOneDelivery(): array
    {
        $driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'monthly_salary' => 15000,
        ]);
        $customer = $this->userWithRole(RoleEnum::Customer, [
            'first_name' => 'Sunita', 'last_name' => '', 'mobile' => '9333333333',
        ]);
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);
        $spot = Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);

        $order = Order::query()->create([
            'order_no' => 'TNK-TEST-00001',
            'customer_id' => $customer->id,
            'driver_id' => $driver->id,
            'tanker_id' => $tanker->id,
            'spot_id' => $spot->id,
            'delivery_address' => 'Kothrud, Pune',
            'delivery_date' => now()->toDateString(),
            'tanker_count' => 1,
            'order_amount' => 1200,
            'refill_charge' => 400,
            'status' => OrderStatusEnum::Delivered,
            'delivered_at' => now(),
        ]);

        return [$driver, $order];
    }
}
