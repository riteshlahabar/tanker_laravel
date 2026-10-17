<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Expense\Models\DriverExpense;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Models\DeliverySchedule;
use App\Modules\Tanker\Models\Tanker;
use App\Modules\Tanker\Models\TankerAssignment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * One account table for all three roles. The admin signs in with an email
 * address; drivers and customers sign in with a mobile number.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'password',
        'address',
        'latitude',
        'longitude',
        'monthly_salary',
        'can_self_order',
        'status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'can_self_order' => 'boolean',
            'system_reserve' => 'boolean',
            'monthly_salary' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Driver side of the tanker history.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TankerAssignment::class, 'driver_id');
    }

    /**
     * The tanker this driver is running right now — the assignment row that
     * has not been closed.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(TankerAssignment::class, 'driver_id')->whereNull('assigned_to');
    }

    public function currentTanker(): ?Tanker
    {
        return $this->currentAssignment?->tanker;
    }

    /**
     * Orders this driver was given — the base for the driver report's
     * aggregates.
     */
    public function deliveredOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    /**
     * Orders this customer placed.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * What this driver spent out of pocket — petrol and the like.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(DriverExpense::class, 'driver_id');
    }

    /**
     * Standing orders held for this customer.
     */
    public function deliverySchedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class, 'customer_id');
    }

    /**
     * A fixed-schedule customer has this turned off: the admin manages their
     * deliveries, so the app refuses a self-placed order.
     */
    public function canSelfOrder(): bool
    {
        return (bool) $this->can_self_order;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Spatie already supplies a role() scope; this one just takes the enum.
     */
    public function scopeOfRole(Builder $query, RoleEnum $role): Builder
    {
        return $query->role($role->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::Admin->value);
    }

    public function isDriver(): bool
    {
        return $this->hasRole(RoleEnum::Driver->value);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(RoleEnum::Customer->value);
    }

    public function roleName(): ?string
    {
        return $this->getRoleNames()->first();
    }
}
