<?php

declare(strict_types=1);

namespace App\Modules\Customer\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Base query already narrowed to the customer role.
     */
    public function scopedQuery(): Builder;

    /**
     * Erase the row outright, rather than the soft delete `remove()` performs.
     * Deliberately narrow to this interface: a hard delete is never the right
     * default, so no other repository inherits one.
     */
    public function purge(int $id): bool;
}
