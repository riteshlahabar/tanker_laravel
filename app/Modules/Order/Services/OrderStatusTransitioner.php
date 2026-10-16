<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Exceptions\InvalidStatusTransitionException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusLog;
use Illuminate\Support\Carbon;

/**
 * The only place an order's status may change. Refuses any move the enum does
 * not allow, stamps the matching timestamp, writes the log row and raises the
 * event — so every caller gets identical behaviour.
 */
final class OrderStatusTransitioner
{
    /**
     * Timestamp column stamped when the order enters each status.
     *
     * @var array<string, string>
     */
    private const STAMPS = [
        'assigned' => 'assigned_at',
        'refilling' => 'refilled_at',
        'delivered' => 'delivered_at',
    ];

    /**
     * @param  array<string, mixed>  $extra  additional columns to write with the move
     */
    public function apply(
        Order $order,
        OrderStatusEnum $to,
        User $actor,
        ?string $note = null,
        array $extra = [],
    ): Order {
        $from = $order->status;

        if (! $from->canTransitionTo($to)) {
            throw InvalidStatusTransitionException::between($from, $to);
        }

        $order->update([...$extra, 'status' => $to, ...$this->stampFor($to)]);

        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'status' => $to,
            'changed_by' => $actor->id,
            'note' => $note,
        ]);

        OrderStatusChanged::dispatch($order->refresh(), $from, $to);

        return $order;
    }

    /**
     * Records the opening status of a brand-new order without a transition check.
     */
    public function recordInitial(Order $order, User $actor): void
    {
        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'status' => $order->status,
            'changed_by' => $actor->id,
            'note' => 'Order placed',
        ]);
    }

    /**
     * Records something that changed about the order *without* moving its
     * status — a driver handover being the one case. Kept here so
     * `order_status_logs` still has exactly one writer, and so the timeline
     * shows the change rather than it happening silently.
     */
    public function recordChange(Order $order, User $actor, string $note): void
    {
        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'status' => $order->status,
            'changed_by' => $actor->id,
            'note' => $note,
        ]);
    }

    /**
     * @return array<string, Carbon>
     */
    private function stampFor(OrderStatusEnum $status): array
    {
        $column = self::STAMPS[$status->value] ?? null;

        return $column === null ? [] : [$column => now()];
    }
}
