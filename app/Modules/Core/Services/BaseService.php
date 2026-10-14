<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Business rules live in services. Every write goes through transaction(),
 * so a module service never repeats begin/commit/rollback.
 */
abstract class BaseService
{
    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
