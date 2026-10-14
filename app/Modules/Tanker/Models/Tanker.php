<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Models;

use App\Models\User;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tanker extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_name',
        'vehicle_number',
        'capacity_litres',
        'model_year',
        'rc_number',
        'insurance_expiry',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'insurance_expiry' => 'date',
            'capacity_litres' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TankerAssignment::class);
    }

    /**
     * The open assignment — the driver currently running this tanker.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TankerAssignment::class)->whereNull('assigned_to');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Tankers with no open assignment, so the driver form only offers free ones.
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereDoesntHave('currentAssignment');
    }

    public function label(): string
    {
        return $this->vehicle_name.' ('.$this->vehicle_number.')';
    }

    public function currentDriver(): ?User
    {
        return $this->currentAssignment?->driver;
    }

    /**
     * Deliveries made with this tanker — the base for the utilisation report.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
