<?php

declare(strict_types=1);

namespace App\Modules\Expense\Models;

use App\Models\User;
use App\Modules\Core\Enums\ExpenseCategoryEnum;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Money a driver spent out of pocket. Never tied to an order — petrol is
 * bought for a shift, not for a delivery.
 */
class DriverExpense extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'tanker_id',
        'expense_date',
        'category',
        'amount',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'category' => ExpenseCategoryEnum::class,
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function tanker(): BelongsTo
    {
        return $this->belongsTo(Tanker::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * True when somebody other than the driver recorded it — the admin filling
     * in for a driver who did not.
     */
    public function enteredByAdmin(): bool
    {
        return $this->created_by !== null && $this->created_by !== $this->driver_id;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from !== null, fn (Builder $q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to !== null, fn (Builder $q) => $q->whereDate('expense_date', '<=', $to));
    }
}
