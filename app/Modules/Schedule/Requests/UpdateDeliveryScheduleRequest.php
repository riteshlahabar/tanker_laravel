<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Requests;

/**
 * Same rules as creating one. The customer is fixed at creation, so it is not
 * accepted here — moving a routine to another customer would orphan the orders
 * it has already produced.
 */
final class UpdateDeliveryScheduleRequest extends StoreDeliveryScheduleRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['customer_id']);

        return $rules;
    }
}
