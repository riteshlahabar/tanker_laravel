<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Models;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * "Not this day." Held against the schedule and the date rather than an order,
 * so one record covers both cases — the order already exists and is cancelled,
 * or nothing is generated yet and the generator must leave the date alone.
 * It survives regeneration, and never moves `next_run_date`.
 */
class ScheduleSkip extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_schedule_id',
        'skip_date',
        'reason',
        'requested_by',
        'order_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skip_date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class, 'delivery_schedule_id');
    }

    /**
     * The order that was cancelled, when one had already been generated.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function scopeOnDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('skip_date', $date->toDateString());
    }
}
