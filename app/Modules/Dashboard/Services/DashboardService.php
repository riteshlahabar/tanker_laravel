<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Dashboard\Contracts\DashboardServiceInterface;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Support\Facades\Cache;

/**
 * Cached briefly so the dashboard never costs the request a pile of counts;
 * the cache is dropped whenever an order changes status.
 */
final class DashboardService implements DashboardServiceInterface
{
    public const CACHE_KEY = 'dashboard.summary';

    private const CACHE_TTL = 60;

    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn (): array => [
            'tankers' => Tanker::query()->where('status', true)->count(),
            'drivers' => User::query()->where('status', true)->role(RoleEnum::Driver->value)->count(),
            'customers' => User::query()->where('status', true)->role(RoleEnum::Customer->value)->count(),
            ...$this->orders->todaySummary(),
        ]);
    }
}
