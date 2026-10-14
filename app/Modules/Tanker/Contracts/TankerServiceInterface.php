<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Contracts;

use App\Modules\Tanker\Exceptions\TankerInUseException;
use App\Modules\Tanker\Models\Tanker;

interface TankerServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tanker;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tanker $tanker, array $data): Tanker;

    /**
     * @throws TankerInUseException
     */
    public function delete(Tanker $tanker): void;

    public function setStatus(Tanker $tanker, bool $status): Tanker;
}
