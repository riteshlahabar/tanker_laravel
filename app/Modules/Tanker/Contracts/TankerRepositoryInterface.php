<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Contracts;

use App\Modules\Core\Contracts\BaseRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Support\Collection;

interface TankerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tankers free to assign, plus the one already held by $keepTankerId so an
     * edit form can still show the current selection.
     *
     * @return Collection<int, Tanker>
     */
    public function assignable(?int $keepTankerId = null): Collection;

    /**
     * The same set shaped for a select element: id => "Name (Number)".
     *
     * @return array<int, string>
     */
    public function assignableOptions(?int $keepTankerId = null): array;
}
