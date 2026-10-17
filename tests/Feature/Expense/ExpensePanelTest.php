<?php

declare(strict_types=1);

namespace Tests\Feature\Expense;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Expense\Models\DriverExpense;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

/**
 * The admin panel's own screens — including the one the owner actually asked
 * for: adding an expense for a driver who did not enter it.
 */
final class ExpensePanelTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $adminUser;

    private User $ramesh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->adminUser = $this->admin();
        $this->ramesh = $this->userWithRole(RoleEnum::Driver, [
            'first_name' => 'Ramesh', 'last_name' => '', 'mobile' => '9111111111', 'monthly_salary' => 15000,
        ]);
    }

    public function test_the_expense_list_loads(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.expense.index'))
            ->assertOk()
            ->assertSee('Driver Expenses');
    }

    public function test_the_admin_adds_an_expense_for_a_driver(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.expense.store'), [
                'driver_id' => $this->ramesh->id,
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Fuel->value,
                'amount' => 3000,
                'notes' => 'Driver did not record it',
            ])
            ->assertRedirect(route('admin.expense.index'))
            ->assertSessionHas('success');

        $expense = DriverExpense::query()->firstOrFail();

        $this->assertSame($this->ramesh->id, $expense->driver_id);
        $this->assertSame($this->adminUser->id, $expense->created_by);
        $this->assertTrue($expense->enteredByAdmin());
    }

    public function test_the_create_form_and_edit_form_both_load(): void
    {
        $expense = $this->expense();

        $this->actingAs($this->adminUser)->get(route('admin.expense.create'))->assertOk();

        $this->actingAs($this->adminUser)
            ->get(route('admin.expense.edit', $expense))
            ->assertOk()
            ->assertSee('Ramesh');
    }

    public function test_the_admin_updates_and_deletes_an_expense(): void
    {
        $expense = $this->expense();

        $this->actingAs($this->adminUser)
            ->put(route('admin.expense.update', $expense), [
                'expense_date' => now()->toDateString(),
                'category' => ExpenseCategoryEnum::Maintenance->value,
                'amount' => 4500,
            ])
            ->assertRedirect(route('admin.expense.index'));

        $this->assertSame(ExpenseCategoryEnum::Maintenance, $expense->refresh()->category);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.expense.destroy', $expense))
            ->assertRedirect();

        $this->assertSoftDeleted('driver_expenses', ['id' => $expense->id]);
    }

    public function test_the_export_runs(): void
    {
        $this->expense();

        $this->actingAs($this->adminUser)
            ->get(route('admin.expense.export'))
            ->assertOk();
    }

    public function test_a_driver_cannot_reach_the_panel(): void
    {
        // The web guard is admin-only, so a driver never gets in at all.
        $this->actingAs($this->ramesh)
            ->get(route('admin.expense.index'))
            ->assertForbidden();
    }

    public function test_the_panel_requires_a_login(): void
    {
        $this->get(route('admin.expense.index'))->assertRedirect(route('login'));
    }

    private function expense(): DriverExpense
    {
        return DriverExpense::query()->create([
            'driver_id' => $this->ramesh->id,
            'expense_date' => now()->toDateString(),
            'category' => ExpenseCategoryEnum::Fuel->value,
            'amount' => 1000,
            'created_by' => $this->ramesh->id,
        ]);
    }
}
