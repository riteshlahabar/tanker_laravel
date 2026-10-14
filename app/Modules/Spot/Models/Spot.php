<?php

declare(strict_types=1);

namespace App\Modules\Spot\Models;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A refill point on the route. `refill_charge` is the one-time cost of filling
 * a tanker here.
 */
class Spot extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'refill_charge',
        'water_type',
        'contact_person',
        'contact_mobile',
        'is_available',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'refill_charge' => 'decimal:2',
            'is_available' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query->active()->where('is_available', true);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Orders spots by straight-line distance, nearest first, so the driver is
     * offered the closest refill point on the route.
     */
    public function scopeNearest(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query
            ->selectRaw(
                '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))'
                .' + sin(radians(?)) * sin(radians(latitude)))) as distance_km',
                [$latitude, $longitude, $latitude],
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('distance_km');
    }

    /**
     * Deliveries refilled at this spot — the base for the refill report.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
