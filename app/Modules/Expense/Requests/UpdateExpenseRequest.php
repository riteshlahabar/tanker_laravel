<?php

declare(strict_types=1);

namespace App\Modules\Expense\Requests;

/**
 * Same fields as a new entry, minus the owner: an edit never moves spending
 * from one driver to another, which would quietly rewrite both their totals.
 * `ExpenseService::update()` strips it again, so neither surface can.
 */
final class UpdateExpenseRequest extends StoreExpenseRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');

        return $expense !== null && ($this->user()?->can('update', $expense) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['driver_id']);

        return $rules;
    }
}
