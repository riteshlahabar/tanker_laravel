<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Spot\Models\Spot;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_no',
        'customer_id',
        'delivery_schedule_id',
        'delivery_address',
        'latitude',
        'longitude',
        'delivery_date',
        'delivery_slot',
        'delivery_slot_id',
        'delivery_at',
        'tanker_count',
        'order_amount',
        'amount_fixed',
        'driver_id',
        'tanker_id',
        'spot_id',
        'refill_charge',
        'status',
        'assigned_at',
        'refilled_at',
        'delivered_at',
        'cancel_reason',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatusEnum::class,
            'delivery_date' => 'date',
            'delivery_at' => 'datetime',
            'assigned_at' => 'datetime',
            'refilled_at' => 'datetime',
            'delivered_at' => 'datetime',
            'order_amount' => 'decimal:2',
            'refill_charge' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'tanker_count' => 'integer',
            'amount_fixed' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function tanker(): BelongsTo
    {
        return $this->belongsTo(Tanker::class);
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    /**
     * The standing order that produced this one, when a schedule did. Null on
     * every ad-hoc order, which is what separates the two in the reports.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class, 'delivery_schedule_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlot::class, 'delivery_slot_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', OrderStatusEnum::Delivered);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [OrderStatusEnum::Delivered, OrderStatusEnum::Cancelled]);
    }

    public function scopeDeliveredBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->delivered()->whereBetween('delivered_at', [$from.' 00:00:00', $to.' 23:59:59']);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('delivery_schedule_id');
    }

    public function scopeAdHoc(Builder $query): Builder
    {
        return $query->whereNull('delivery_schedule_id');
    }

    public function isScheduled(): bool
    {
        return $this->delivery_schedule_id !== null;
    }

    public function isAssigned(): bool
    {
        return $this->driver_id !== null;
    }
}
