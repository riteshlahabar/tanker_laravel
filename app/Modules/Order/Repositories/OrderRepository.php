<?php

declare(strict_types=1);

namespace App\Modules\Order\Repositories;

use App\Models\User;
use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function model(): string
    {
        return Order::class;
    }

    public function visibleTo(User $user): Builder
    {
        $query = Order::query();

        return match (true) {
            $user->isDriver() => $query->where('driver_id', $user->id),
            $user->isCustomer() => $query->where('customer_id', $user->id),
            default => $query,
        };
    }

    public function findForUser(User $user, int $id): Order
    {
        return $this->visibleTo($user)->findOrFail($id);
    }

    /**
     * @return array<string, int|float>
     */
    public function todaySummary(): array
    {
        $today = now()->toDateString();

        $counts = Order::query()
            ->selectRaw('status, count(*) as total, sum(order_amount) as amount')
            ->whereDate('created_at', $today)
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status instanceof OrderStatusEnum ? $row->status->value : (string) $row->status);

        $delivered = Order::query()->whereDate('delivered_at', $today);

        return [
            'orders_today' => (int) $counts->sum('total'),
            'pending' => (int) ($counts[OrderStatusEnum::Pending->value]->total ?? 0),
            'delivered_today' => (int) $delivered->clone()->count(),
            'revenue_today' => (float) $delivered->clone()->sum('order_amount'),
            'driver_earning_today' => (float) $delivered->clone()->sum('driver_earning'),
        ];
    }
}
