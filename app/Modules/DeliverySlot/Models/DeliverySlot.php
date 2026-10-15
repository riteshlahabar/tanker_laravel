<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A named delivery window. `start_time` is the anchor the six-hour skip
 * notice counts back from.
 */
class DeliverySlot extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = ['name', 'start_time', 'end_time', 'sort_order', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('start_time');
    }

    public function label(): string
    {
        return sprintf('%s (%s – %s)', $this->name, $this->startLabel(), $this->endLabel());
    }

    public function startLabel(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A');
    }

    public function endLabel(): string
    {
        return Carbon::parse($this->end_time)->format('g:i A');
    }

    /**
     * The slot's start on a given date — the concrete moment a delivery in
     * this window is due.
     */
    public function startsOn(string $date): Carbon
    {
        return Carbon::parse($date.' '.Carbon::parse($this->start_time)->format('H:i:s'));
    }
}
