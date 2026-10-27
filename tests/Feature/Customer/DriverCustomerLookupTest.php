<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * `GET /api/driver/customers` — finding the customer a driver is about to
 * write an order for.
 *
 * The owner chose to give drivers the whole searchable customer list rather
 * than a mobile-number lookup, so the containment that remains is in **what
 * each row carries**: `CustomerLookupResource` sends four fields, and this is
 * the test that notices if it ever starts sending the account.
 */
final class DriverCustomerLookupTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->driver = $this->userWithRole(RoleEnum::Driver, ['mobile' => '9111111111']);
    }

    public function test_a_driver_searches_customers_by_name_and_by_mobile(): void
    {
        $this->customer('Sunita', 'Joshi', '9333333333');
        $this->customer('Mahesh', 'Pawar', '9444444444');

        $all = $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index'))
            ->assertOk();

        $this->assertCount(2, $all->json('data.items'));

        $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index', ['search' => 'Joshi']))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.full_name', 'Sunita Joshi');

        // The driver is given a number far more often than a surname.
        $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index', ['search' => '9444']))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.full_name', 'Mahesh Pawar');
    }

    public function test_the_row_carries_only_what_the_picker_needs(): void
    {
        $this->customer('Sunita', 'Joshi', '9333333333');

        $row = $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index'))
            ->assertOk()
            ->json('data.items.0');

        // Name, number, address — the three the form shows, plus the id.
        $this->assertSame(['id', 'full_name', 'mobile', 'address'], array_keys($row));
        $this->assertSame('Flat 4, Kothrud', $row['address']);

        // Not the account: `can_self_order` and `status` are decisions about a
        // customer's relationship with the office, and `CustomerResource`
        // sends them to the admin. A driver picking a name has no use for
        // either, so they are not on their phone.
        $this->assertArrayNotHasKey('can_self_order', $row);
        $this->assertArrayNotHasKey('status', $row);
    }

    public function test_a_deactivated_customer_is_not_offered(): void
    {
        $this->customer('Sunita', 'Joshi', '9333333333');
        $this->customer('Closed', 'Account', '9555555555', ['status' => false]);

        $response = $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index'))
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);

        // Listing one would only produce a refusal at submit, so the driver
        // never sees it.
        $this->assertSame('Sunita Joshi', $response->json('data.items.0.full_name'));
    }

    public function test_only_customers_appear_in_the_lookup(): void
    {
        $this->customer('Sunita', 'Joshi', '9333333333');

        $response = $this->actingAsApi($this->driver)
            ->getJson(route('api.driver.customers.index'))
            ->assertOk();

        // The repository's role scope, not a filter in the controller — the
        // driver and admin accounts in this test are in the same users table.
        $this->assertSame(['Sunita Joshi'], array_column($response->json('data.items'), 'full_name'));
    }

    public function test_a_customer_cannot_read_the_customer_list(): void
    {
        $customer = $this->customer('Sunita', 'Joshi', '9333333333');

        $this->actingAsApi($customer)
            ->getJson(route('api.driver.customers.index'))
            ->assertForbidden();
    }

    public function test_the_lookup_does_not_open_the_admins_customer_endpoints(): void
    {
        // `customer.lookup` is a separate permission precisely so that giving
        // it to a driver does not hand them `customer.view` and the writes
        // that sit beside it.
        $this->actingAsApi($this->driver)
            ->getJson(route('api.admin.customers.index'))
            ->assertForbidden();

        $this->actingAsApi($this->driver)
            ->postJson(route('api.admin.customers.store'), [])
            ->assertForbidden();

        $this->assertFalse($this->driver->can('customer.view'));
        $this->assertTrue($this->driver->can('customer.lookup'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function customer(string $first, string $last, string $mobile, array $attributes = []): User
    {
        return $this->userWithRole(RoleEnum::Customer, [
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'address' => 'Flat 4, Kothrud',
            ...$attributes,
        ]);
    }
}
