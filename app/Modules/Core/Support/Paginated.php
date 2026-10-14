<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Every paginated API list has the same shape, so the app has one list-parsing
 * path rather than one per screen.
 */
final class Paginated
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    /**
     * @param  class-string<JsonResource>  $resource
     * @return array<string, mixed>
     */
    public static function of(LengthAwarePaginator $page, string $resource): array
    {
        return [
            'items' => $resource::collection($page->items())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'has_more' => $page->hasMorePages(),
            ],
        ];
    }

    /**
     * Clamped so a client cannot ask for the whole table in one request.
     */
    public static function perPage(mixed $requested): int
    {
        $value = (int) ($requested ?: self::DEFAULT_PER_PAGE);

        return max(1, min($value, self::MAX_PER_PAGE));
    }
}
