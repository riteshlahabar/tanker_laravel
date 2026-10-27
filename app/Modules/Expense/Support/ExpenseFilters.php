<?php

declare(strict_types=1);

namespace App\Modules\Expense\Support;

use App\Modules\Core\Enums\ExpenseCategoryEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Driver / category / date range / free text — the four things the admin's
 * Driver Expenses screen filters by, carried as one value so the **list and
 * its totals are built from the same query**.
 *
 * That is the whole reason this exists. The app's screen shows a spending
 * total above the rows it lists, and a total that answered a different
 * question from the list under it would be worse than no total at all: the
 * admin would reconcile two figures that were never meant to agree. Both
 * endpoints now take these filters and hand them to one repository method, so
 * there is no second copy of the filter logic to drift.
 */
final readonly class ExpenseFilters
{
    public function __construct(
        public ?int $driverId = null,
        public ?string $category = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $search = null,
    ) {}

    /**
     * The validation both endpoints apply, in one place for the same reason
     * the filters themselves are.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'driver_id' => ['nullable', 'integer'],
            'category' => ['nullable', Rule::in(ExpenseCategoryEnum::values())],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Empty strings become null, because the app sends a cleared filter as an
     * empty query parameter rather than dropping it.
     */
    public static function fromRequest(Request $request): self
    {
        $driverId = $request->integer('driver_id');

        return new self(
            driverId: $driverId > 0 ? $driverId : null,
            category: $request->string('category')->value() ?: null,
            from: $request->string('from')->value() ?: null,
            to: $request->string('to')->value() ?: null,
            search: $request->string('search')->value() ?: null,
        );
    }
}
