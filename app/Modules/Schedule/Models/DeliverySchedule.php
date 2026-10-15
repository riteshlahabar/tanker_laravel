<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Models;

use App\Models\User;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Support\SchedulePattern;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A standing order: this customer gets a tanker on a fixed rhythm without
 * ever placing an order. The nightly generator turns due dates into ordinary
 * orders, after which nothing downstream can tell the difference.
 */
class DeliverySchedule extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'interval_days',
        'weekdays',
        'quantity_litres',
        'order_amount',
        'delivery_slot_id',
        'delivery_time',
        'delivery_address',
        'latitude',
        'longitude',
        'preferred_driver_id',
        'start_date',
        'end_date',
        'next_run_date',
        'is_paused',
        'pause_until',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'interval_days' => 'integer',
            'quantity_litres' => 'integer',
            'order_amount' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_run_date' => 'date',
            'is_paused' => 'boolean',
            'pause_until' => 'date',
            'status' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function preferredDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preferred_driver_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Orders this schedule has produced so far.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_schedule_id')->orderByDesc('delivery_date');
    }

    public function skips(): HasMany
    {
        return $this->hasMany(ScheduleSkip::class)->orderBy('skip_date');
    }

    /**
     * The rhythm as a value object, so the date arithmetic lives in one place
     * rather than being re-derived by every caller.
     */
    public function pattern(): SchedulePattern
    {
        return new SchedulePattern(
            intervalDays: (int) $this->interval_days,
            weekdays: $this->weekdays,
            startDate: Carbon::parse($this->start_date),
            endDate: $this->end_date ? Carbon::parse($this->end_date) : null,
        );
    }

    public function patternLabel(): string
    {
        return $this->pattern()->label();
    }

    /**
     * Paused outright, or paused until a date this one falls before.
     */
    public function isPausedOn(Carbon $date): bool
    {
        if (! $this->is_paused) {
            return false;
        }

        if ($this->pause_until === null) {
            return true;
        }

        return $date->copy()->startOfDay()->lte(Carbon::parse($this->pause_until)->startOfDay());
    }

    public function hasEnded(Carbon $date): bool
    {
        return $this->end_date !== null
            && $date->copy()->startOfDay()->gt(Carbon::parse($this->end_date)->startOfDay());
    }

    /**
     * When the delivery is due on a given date: the slot's start time, or the
     * exact time the schedule names. This is what `orders.delivery_at` is
     * resolved from, and what the six hours' notice counts back from.
     */
    public function dueAt(Carbon $date): Carbon
    {
        $day = $date->copy()->startOfDay();

        if ($this->delivery_time !== null) {
            return $day->copy()->setTimeFromTimeString((string) $this->delivery_time);
        }

        if ($this->slot !== null) {
            return $this->slot->startsOn($day->toDateString());
        }

        return $day;
    }

    /**
     * The label written onto the order, kept because `delivery_slot` is free
     * text on older orders and still shown on the timeline.
     */
    public function slotLabel(): ?string
    {
        if ($this->slot !== null) {
            return $this->slot->name;
        }

        return $this->delivery_time === null
            ? null
            : Carbon::parse($this->delivery_time)->format('g:i A');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Schedules the generator should look at for a target date: switched on,
     * not paused, and due on or before that date.
     */
    public function scopeDueBy(Builder $query, Carbon $date): Builder
    {
        return $query->active()
            ->where('is_paused', false)
            ->whereNotNull('next_run_date')
            ->whereDate('next_run_date', '<=', $date->toDateString())
            ->where(fn (Builder $q) => $q
                ->whereNull('end_date')
                ->orWhereDate('end_date', '>=', $date->toDateString()));
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }
}
