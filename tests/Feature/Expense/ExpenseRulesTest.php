<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Expense\Contracts\ExpenseRepositoryInterface;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Expense\Requests\StoreExpenseRequest;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\ApiActingAs;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * The ownership rule and the admin override — the two things that decide who
 * may touch which row. There is no approval workflow: the owner chose to
 * record spending rather than review it (2026-10-17).
 */
final class ExpenseRulesTest extends TestCase
{
    use ApiActingAs, CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $ramesh;

    private User $suresh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->adminUser = $this->admin();
        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'mobile' => '9111111111', 'monthly_salary' => 15000,
        ]);
        $this->suresh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Suresh', 'mobile' => '9222222222', 'monthly_salary' => 18000,
        ]);
    }

    public function test_a_driver_records_their_own_expense(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.expenses.store'), [
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 2500,
                'notes' => 'Diesel, bill 4471',
            ])
            ->assertCreated()
            ->assertJsonPath('data.category', 'fuel')
            ->assertJsonPath('data.amount', 2500)
            // Recorded by the driver themselves, not the office.
            ->assertJsonPath('data.entered_by_admin', false);

        $this->assertDatabaseHas('driver_expenses', [
            'driver_id' => $this->ramesh->id,
            'created_by' => $this->ramesh->id,
        ]);
    }

    public function test_a_driver_cannot_file_an_expense_against_another_driver(): void
    {
        // driver_id is ignored outright for a driver: the owner comes from the
        // token, so there is nothing to smuggle in the body.
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.expenses.store'), [
                'driver_id' => $this->suresh->id,
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 2500,
            ])
            ->assertCreated()
            ->assertJsonPath('data.driver_id', $this->ramesh->id);

        $this->assertSame(0, DriverExpense::query()->where('driver_id', $this->suresh->id)->count());
    }

    public function test_a_driver_sees_only_their_own_expenses(): void
    {
        $this->expenseFor($this->ramesh);
        $this->expenseFor($this->suresh);

        $response = $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.expenses.index'))
            ->assertOk();

        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame($this->ramesh->id, $response->json('data.items.0.driver_id'));
    }

    public function test_a_driver_cannot_edit_or_delete_another_drivers_expense(): void
    {
        $theirs = $this->expenseFor($this->suresh);

        $this->actingAsApi($this->ramesh)
            ->putJson(route('api.driver.expenses.update', $theirs), [
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Food->value,
                'amount' => 1,
            ])
            ->assertForbidden();

        $this->actingAsApi($this->ramesh)
            ->deleteJson(route('api.driver.expenses.destroy', $theirs))
            ->assertForbidden();

        $this->assertDatabaseHas('driver_expenses', ['id' => $theirs->id, 'deleted_at' => null]);
    }

    public function test_a_driver_may_correct_their_own_entry(): void
    {
        // Nothing locks a row: with no approval step, a driver fixing their own
        // typo should not have to ask the office.
        $mine = $this->expenseFor($this->ramesh);

        $this->actingAsApi($this->ramesh)
            ->putJson(route('api.driver.expenses.update', $mine), [
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Toll->value,
                'amount' => 90,
            ])
            ->assertOk()
            ->assertJsonPath('data.category', 'toll')
            ->assertJsonPath('data.amount', 90);
    }

    public function test_the_admin_records_an_expense_for_a_driver_who_did_not(): void
    {
        // The case the owner asked for.
        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.expenses.store'), [
                'driver_id' => $this->ramesh->id,
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 3000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.driver_id', $this->ramesh->id)
            // created_by differs from driver_id, which is what makes "the office
            // added this" visible rather than guessed.
            ->assertJsonPath('data.entered_by_admin', true);
    }

    public function test_the_admin_must_name_a_driver(): void
    {
        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.expenses.store'), [
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 3000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_id');
    }

    public function test_an_expense_cannot_be_filed_against_a_customer(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $this->actingAsApi($this->adminUser)
            ->postJson(route('api.admin.expenses.store'), [
                'driver_id' => $customer->id,
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 500,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_id');
    }

    public function test_the_admin_sees_and_may_delete_every_drivers_expense(): void
    {
        $this->expenseFor($this->ramesh);
        $theirs = $this->expenseFor($this->suresh);

        $response = $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.expenses.index'))
            ->assertOk();

        $this->assertCount(2, $response->json('data.items'));

        $this->actingAsApi($this->adminUser)
            ->deleteJson(route('api.admin.expenses.destroy', $theirs))
            ->assertOk();

        $this->assertSoftDeleted('driver_expenses', ['id' => $theirs->id]);
    }

    public function test_an_expense_cannot_be_dated_in_the_future(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.expenses.store'), [
                'expense_date' => now()->addDay()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 2500,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expense_date');
    }

    public function test_an_expense_cannot_be_backdated_past_the_limit(): void
    {
        $this->actingAsApi($this->ramesh)
            ->postJson(route('api.driver.expenses.store'), [
                'expense_date' => now()->subDays(StoreExpenseRequest::BACKDATE_DAYS + 1)->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 2500,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expense_date');
    }

    public function test_an_edit_never_moves_spending_to_another_driver(): void
    {
        $mine = $this->expenseFor($this->ramesh);

        $this->actingAsApi($this->adminUser)
            ->putJson(route('api.admin.expenses.update', $mine), [
                'driver_id' => $this->suresh->id,
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 2500,
            ])
            ->assertOk()
            // Still Ramesh's: moving it would quietly rewrite both their totals.
            ->assertJsonPath('data.driver_id', $this->ramesh->id);
    }

    public function test_the_summary_totals_this_month_by_category(): void
    {
        $this->expenseFor($this->ramesh, ['category' => ExpenseCategoryEnum::Fuel->value, 'amount' => 2000]);
        $this->expenseFor($this->ramesh, ['category' => ExpenseCategoryEnum::Fuel->value, 'amount' => 500]);
        $this->expenseFor($this->ramesh, ['category' => ExpenseCategoryEnum::Toll->value, 'amount' => 90]);
        $this->expenseFor($this->suresh, ['category' => ExpenseCategoryEnum::Fuel->value, 'amount' => 9999]);

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.driver.expenses.summary'))
            ->assertOk()
            ->assertJsonPath('data.month.fuel', 2500)
            ->assertJsonPath('data.month.toll', 90)
            // Every category is present even at zero, so the app's header does
            // not change shape with the data.
            ->assertJsonPath('data.month.food', 0)
            ->assertJsonPath('data.month.total', 2590)
            ->assertJsonPath('data.month.entries', 3);
    }

    public function test_a_customer_cannot_reach_the_expense_endpoints(): void
    {
        $customer = $this->userWithRole(RoleEnum::Customer, ['mobile' => '9333333333']);

        $this->actingAsApi($customer)
            ->getJson(route('api.driver.expenses.index'))
            ->assertForbidden();

        $this->actingAsApi($customer)
            ->getJson(route('api.admin.expenses.index'))
            ->assertForbidden();
    }

    public function test_a_driver_cannot_reach_the_admin_list(): void
    {
        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.admin.expenses.index'))
            ->assertForbidden();

        $this->actingAsApi($this->ramesh)
            ->getJson(route('api.admin.expenses.summary'))
            ->assertForbidden();
    }

    /**
     * The admin screen shows a spending total above the rows it lists, so the
     * two have to answer the same question — the figure is the sum of exactly
     * what is listed, not of everything.
     *
     * Both endpoints go through `ExpenseRepository::filtered()` for that
     * reason; this is the test that would fail if either grew a filter of its
     * own.
     */
    public function test_the_admin_summary_totals_exactly_what_the_filtered_list_returns(): void
    {
        $this->expenseFor($this->ramesh, ['amount' => 1200]);
        $this->expenseFor($this->ramesh, [
            'amount' => 300, 'category' => ExpenseCategoryEnum::Toll->value,
        ]);
        $this->expenseFor($this->suresh, ['amount' => 900]);

        $query = ['driver_id' => $this->ramesh->id];

        $list = $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.expenses.index', $query))
            ->assertOk();

        $summary = $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.expenses.summary', $query))
            ->assertOk();

        $this->assertCount(2, $list->json('data.items'));

        // Integral money arrives without its `.0` — PHP serialises 1500.0 as
        // 1500 — so these read as ints here exactly as the driver summary's
        // assertions above do. The app reads them through `JsonRead.asDouble`
        // for the same reason (CLAUDE.md rule 7).
        $summary
            ->assertJsonPath('data.filtered.entries', 2)
            ->assertJsonPath('data.filtered.total', 1500)
            ->assertJsonPath('data.filtered.fuel', 1200)
            ->assertJsonPath('data.filtered.toll', 300)
            // Every category is present even at zero, so the header never
            // changes shape with the data.
            ->assertJsonPath('data.filtered.maintenance', 0)
            // `month` deliberately ignores the filters: it is the fixed "what
            // are we spending" reference, so it still counts Suresh's 900.
            ->assertJsonPath('data.month.total', 2400)
            ->assertJsonPath('data.month.entries', 3);
    }

    public function test_the_admin_summary_follows_a_category_and_date_filter_too(): void
    {
        $this->expenseFor($this->ramesh, ['amount' => 1200]);
        $this->expenseFor($this->ramesh, [
            'amount' => 450,
            'category' => ExpenseCategoryEnum::Toll->value,
            'expense_date' => now()->subMonths(2)->toDateString(),
        ]);

        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.expenses.summary', ['category' => 'toll']))
            ->assertOk()
            ->assertJsonPath('data.filtered.total', 450)
            // Out of this month, so the reference figure excludes it.
            ->assertJsonPath('data.month.total', 1200);

        $this->actingAsApi($this->adminUser)
            ->getJson(route('api.admin.expenses.summary', [
                'from' => now()->subMonths(3)->toDateString(),
                'to' => now()->subMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('data.filtered.total', 450);
    }

    public function test_the_repository_scope_is_what_hides_other_drivers_rows(): void
    {
        $this->expenseFor($this->ramesh);
        $this->expenseFor($this->suresh);

        $expenses = app(ExpenseRepositoryInterface::class);

        $this->assertSame(1, $expenses->visibleTo($this->ramesh)->count());
        $this->assertSame(2, $expenses->visibleTo($this->adminUser)->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function expenseFor(User $driver, array $attributes = []): DriverExpense
    {
        return DriverExpense::query()->create([
            'driver_id' => $driver->id,
            'expense_date' => now()->toDateString(),
            'category' => ExpenseCategoryEnum::Fuel->value,
            'amount' => 1000,
            'created_by' => $driver->id,
            ...$attributes,
        ]);
    }
}
