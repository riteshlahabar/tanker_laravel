<?php

declare(strict_types=1);

namespace App\Modules\Expense\Resources;

use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DriverExpense
 */
final class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'driver_id' => $this->driver_id,
            'driver_name' => $this->driver?->full_name,
            'tanker_id' => $this->tanker_id,
            'tanker_label' => $this->tanker?->label(),
            'expense_date' => $this->expense_date?->toDateString(),
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            // So the driver's own list can say "added by the office" rather
            // than leaving them wondering where a row came from.
            'entered_by_admin' => $this->enteredByAdmin(),
            'entered_by_name' => $this->enteredBy?->full_name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
