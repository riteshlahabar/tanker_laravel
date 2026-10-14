<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\Route;

/**
 * Resolves config/menu.php entries for the sidebar. A route that does not exist
 * yet renders as "#" instead of throwing, so the menu can list a module before
 * that module ships.
 */
final class Menu
{
    public static function url(?string $route): string
    {
        if ($route === null || ! Route::has($route)) {
            return '#';
        }

        return route($route);
    }

    /**
     * @param  array{route?: string|null, children?: array<int, array{route?: string|null}>}  $item
     */
    public static function isActive(array $item): bool
    {
        if (self::matches($item['route'] ?? null)) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if (self::matches($child['route'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private static function matches(?string $route): bool
    {
        if ($route === null) {
            return false;
        }

        $current = Route::currentRouteName();

        if ($current === null) {
            return false;
        }

        // admin.tanker.index also lights up on admin.tanker.create / .edit
        $prefix = implode('.', array_slice(explode('.', $route), 0, -1));

        return $current === $route || str_starts_with($current, $prefix.'.');
    }
}
