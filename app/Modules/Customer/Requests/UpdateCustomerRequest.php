<?php

declare(strict_types=1);

namespace App\Modules\Customer\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

final class UpdateCustomerRequest extends StoreCustomerRequest
{
    /**
     * Left blank, the password is kept as it is.
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['nullable', 'string', 'confirmed', Password::min(8)];
    }

    /**
     * Same unconditional index as on create — see the parent — minus this
     * customer's own row, so saving the form without touching the number works.
     */
    protected function uniqueMobile(): Unique
    {
        return Rule::unique('users', 'mobile')->ignore($this->route('customer'));
    }
}
