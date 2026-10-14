<?php

declare(strict_types=1);

namespace App\Modules\Setting\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    private const CACHE_KEY = 'settings.all';

    public function model(): string
    {
        return Setting::class;
    }

    /**
     * @return array<string, string|null>
     */
    public function all_(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => $this->model->newQuery()->pluck('value', 'key')->all(),
        );
    }

    public function value(string $key, ?string $default = null): ?string
    {
        return $this->all_()[$key] ?? $default;
    }

    public function put(string $key, ?string $value): void
    {
        $this->model->newQuery()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }
}
