<?php

declare(strict_types=1);

namespace App\Modules\Spot\Contracts;

use App\Modules\Spot\Models\Spot;

interface SpotServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Spot;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Spot $spot, array $data): Spot;

    public function delete(Spot $spot): void;

    public function setStatus(Spot $spot, bool $status): Spot;
}
