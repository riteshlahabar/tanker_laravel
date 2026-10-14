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
}
