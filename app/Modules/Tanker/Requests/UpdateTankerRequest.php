<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateTankerRequest extends StoreTankerRequest
{
    protected function uniqueVehicleNumber(): Unique
    {
        return Rule::unique('tankers', 'vehicle_number')
            ->ignore($this->route('tanker'))
            ->whereNull('deleted_at');
    }
}
