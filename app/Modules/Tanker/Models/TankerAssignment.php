<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Which tanker a driver drives, over time. The row with a null `assigned_to`
 * is the current one.
 */
class TankerAssignment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_id',
        'tanker_id',
        'assigned_from',
        'assigned_to',
        'reason',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
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

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }
}
