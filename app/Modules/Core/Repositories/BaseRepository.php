<?php

declare(strict_types=1);

namespace App\Modules\Core\Repositories;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Eloquent\BaseRepository as PrettusRepository;

/**
 * Data access only — no business rules, no transactions, no events. Those
 * belong to the service layer.
 */
abstract class BaseRepository extends PrettusRepository implements BaseRepositoryInterface
{
    public function findOrFail(int $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function modify(int $id, array $attributes): Model
    {
        $record = $this->findOrFail($id);
        $record->update($attributes);

        return $record->refresh();
    }

    public function remove(int $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function toggleStatus(int $id, bool $status): Model
    {
        return $this->modify($id, ['status' => $status]);
    }
}
