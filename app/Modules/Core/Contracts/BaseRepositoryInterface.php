<?php

declare(strict_types=1);

namespace App\Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * The data-access surface every module repository exposes. Services depend on
 * their module's interface, which extends this one — never on a concrete class.
 */
interface BaseRepositoryInterface
{
    public function findOrFail(int $id): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function modify(int $id, array $attributes): Model;

    public function remove(int $id): bool;

    public function toggleStatus(int $id, bool $status): Model;
}
