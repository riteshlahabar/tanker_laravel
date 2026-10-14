<?php

declare(strict_types=1);

namespace App\Modules\Driver\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

interface DriverRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Base query already narrowed to the driver role.
     */
    public function scopedQuery(): Builder;
}
