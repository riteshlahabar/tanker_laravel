<?php

declare(strict_types=1);

namespace App\Modules\Expense\Requests;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One set of rules for both role surfaces. `driver_id` is required of an admin
 * and refused from a driver, so the app and the panel validate identically and
 * a driver cannot file spending against somebody else.
 */
class StoreExpenseRequest extends FormRequest
{
    /** A driver cannot quietly fill in last year. */
    public const BACKDATE_DAYS = 60;

    public function authorize(): bool
    {
        return $this->user()?->can('create', DriverExpense::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'driver_id' => [
                Rule::requiredIf(fn (): bool => (bool) $this->user()?->isAdmin()),
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                $this->mustBeADriver(),
            ],
            'tanker_id' => ['nullable', 'integer', Rule::exists('tankers', 'id')->whereNull('deleted_at')],
            'expense_date' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:'.now()->subDays(self::BACKDATE_DAYS)->toDateString(),
            ],
            'category' => ['required', Rule::in(ExpenseCategoryEnum::values())],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expense_date.before_or_equal' => 'An expense cannot be dated in the future.',
            'expense_date.after_or_equal' => 'An expense can only be backdated '
                .self::BACKDATE_DAYS.' days.',
        ];
    }

    /**
     * The id must belong to an actual driver — "exists in users" alone would
     * let an expense be filed against a customer or the owner.
     */
    protected function mustBeADriver(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null) {
                return;
            }

            $isDriver = User::query()
                ->whereKey($value)
                ->role(RoleEnum::Driver->value)
                ->exists();

            if (! $isDriver) {
                $fail('The selected account is not a driver.');
            }
        };
    }
}
