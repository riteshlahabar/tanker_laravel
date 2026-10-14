<?php

declare(strict_types=1);

namespace App\Modules\Setting\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;

interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return array<string, string|null>
     */
    public function all_(): array;

    public function value(string $key, ?string $default = null): ?string;

    public function put(string $key, ?string $value): void;
}
