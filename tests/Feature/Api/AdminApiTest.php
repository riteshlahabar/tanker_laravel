<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class AdminApiTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->adminUser = $this->admin();
    }

    public function test_the_dashboard_summary_returns_the_counters(): void
    {
        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.dashboard'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'tankers', 'drivers', 'customers',
                'orders_today', 'pending', 'delivered_today', 'revenue_today', 'driver_earning_today',
            ]]);
    }

    public function test_an_admin_can_create_a_tanker_over_the_api(): void
    {
        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.tankers.store'), [
                'vehicle_name' => 'Water Tanker 1',
                'vehicle_number' => 'mh12ab1234',
                'capacity_litres' => 5000,
                'status' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.vehicle_number', 'MH12AB1234');

        $this->assertDatabaseHas('tankers', ['vehicle_number' => 'MH12AB1234']);
    }

    public function test_tanker_validation_applies_to_the_api_too(): void
    {
        Tanker::query()->create(['vehicle_name' => 'First', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.tankers.store'), [
                'vehicle_name' => 'Second',
                'vehicle_number' => 'MH12AB1234',
                'status' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vehicle_number');
    }

    public function test_an_admin_can_create_a_driver_with_a_tanker(): void
    {
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);

        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.drivers.store'), [
                'first_name' => 'Ramesh',
                'mobile' => '9111111111',
                'password' => 'driver-pass-1',
                'password_confirmation' => 'driver-pass-1',
                'tanker_id' => $tanker->id,
                'per_delivery_rate' => 150,
                'status' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.tanker.vehicle_number', 'MH12AB1234');

        $driver = User::query()->where('mobile', '9111111111')->firstOrFail();
        $this->assertTrue($driver->hasRole(RoleEnum::Driver->value));
    }

    public function test_a_customer_id_cannot_be_updated_through_the_driver_endpoint(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $this->actingAsApi($this->adminUser)
            ->putJson(route('api.admin.drivers.update', $customer->id), [
                'first_name' => 'Hijacked',
                'mobile' => '9333333333',
                'per_delivery_rate' => 0,
                'status' => 1,
            ])
            ->assertNotFound();
    }

    public function test_the_admin_order_list_is_filterable(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $this->order($customer, ['status' => OrderStatusEnum::Pending]);
        $this->order($customer, ['status' => OrderStatusEnum::Delivered, 'delivered_at' => now()]);

        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.orders.index', ['status' => 'delivered']))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.status', 'delivered');
    }

    public function test_an_admin_can_assign_an_order_over_the_api(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);
        $driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'per_delivery_rate' => 150,
        ]);
        $tanker = Tanker::query()->create(['vehicle_name' => 'One', 'vehicle_number' => 'MH12AB1234']);
        $tanker->assignments()->create(['driver_id' => $driver->id, 'assigned_from' => now()->toDateString()]);
        $spot = Spot::query()->create(['name' => 'Hadapsar Borewell', 'refill_charge' => 400]);

        $order = $this->order($customer);

        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.orders.assign', $order), [
                'driver_id' => $driver->id,
                'spot_id' => $spot->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.driver.full_name', 'Ramesh')
            ->assertJsonPath('data.refill_charge', 400)
            ->assertJsonPath('data.driver_earning', 150);
    }

    public function test_assigning_a_driver_with_no_tanker_is_refused_with_a_reason(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);
        $driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
        $order = $this->order($customer);

        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.orders.assign', $order), ['driver_id' => $driver->id])
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This driver has no tanker assigned. Give them a tanker first.');
    }

    public function test_the_report_index_lists_the_six_reports(): void
    {
        $response = $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.reports.index'))
            ->assertOk();

        $this->assertSame([
            'driver-earning', 'daily-delivery', 'spot-refill', 'customer-order', 'tanker-utilisation',
            'schedule-adherence',
        ], array_column($response->json('data'), 'key'));
    }

    public function test_a_report_returns_headings_rows_and_totals(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);
        $driver = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'per_delivery_rate' => 150,
        ]);

        $this->order($customer, [
            'driver_id' => $driver->id,
            'status' => OrderStatusEnum::Delivered,
            'delivered_at' => now(),
            'driver_earning' => 150,
        ]);

        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.reports.show', ['report' => 'driver-earning']))
            ->assertOk()
            ->assertJsonPath('data.key', 'driver-earning')
            ->assertJsonPath('data.title', 'Driver Delivery & Earning')
            ->assertJsonPath('data.headings.0', 'Driver')
            ->assertJsonPath('data.rows.0.0', 'Ramesh')
            ->assertJsonPath('data.totals.Total earning', '₹ 150.00');
    }

    public function test_an_unknown_report_is_not_found(): void
    {
        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.reports.show', ['report' => 'made-up']))
            ->assertNotFound();
    }

    public function test_an_admin_access_token_carries_every_permission(): void
    {
        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $accessToken = $this->adminUser->tokens()->where('name', 'access:test-device')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            PermissionEnum::values(),
            $accessToken->abilities,
        );
    }

    public function test_a_refresh_token_can_only_refresh(): void
    {
        $refresh = $this->apiRefreshTokenFor($this->adminUser);

        // Holding a refresh token must not open the rest of the API.
        $this->withToken($refresh)->getJson(route('api.admin.dashboard'))->assertForbidden();
        $this->withToken($refresh)->postJson(route('api.auth.refresh'))->assertOk();
    }

    public function test_the_admin_can_read_settings_over_the_api(): void
    {
        $this->seed(SettingSeeder::class);

        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.settings.show'))
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'company_name', 'admin_whatsapp_number', 'default_driver_rate',
                'default_order_amount', 'price_per_litre', 'daily_report_time',
            ]]);
    }

    public function test_the_admin_can_update_settings_over_the_api(): void
    {
        $this->actingAsApi($this->adminUser)
            ->putJson(route('api.admin.settings.update'), [
                'company_name' => 'Tanker Co',
                'admin_whatsapp_number' => '919812345678',
                'default_driver_rate' => 175,
                'default_order_amount' => 1000,
                'price_per_litre' => 0,
                'daily_report_time' => '23:59',
            ])
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Tanker Co')
            ->assertJsonPath('data.admin_whatsapp_number', '919812345678');
    }

    public function test_updating_settings_validates_the_fields(): void
    {
        $this->actingAsApi($this->adminUser)
            ->putJson(route('api.admin.settings.update'), ['company_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'default_driver_rate']);
    }

    public function test_every_api_endpoint_rejects_an_anonymous_request(): void
    {
        $routes = [
            route('api.admin.dashboard'),
            route('api.admin.orders.index'),
            route('api.admin.tankers.index'),
            route('api.admin.settings.show'),
            route('api.admin.reports.index'),
            route('api.customer.orders.index'),
            route('api.driver.orders.index'),
            route('api.spots.index'),
        ];

        foreach ($routes as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
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
