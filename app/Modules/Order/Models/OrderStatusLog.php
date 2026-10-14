<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per status change — the customer's tracking timeline and the audit
 * trail for who moved the order.
 */
class OrderStatusLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['order_id', 'status', 'changed_by', 'note'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => OrderStatusEnum::class];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
