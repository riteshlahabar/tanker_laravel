<?php

declare(strict_types=1);

namespace App\Modules\Driver\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

final class UpdateDriverRequest extends StoreDriverRequest
{
    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['nullable', 'string', 'confirmed', Password::min(8)];
    }

    protected function uniqueMobile(): Unique
    {
        return Rule::unique('users', 'mobile')
            ->ignore($this->route('driver'))
            ->whereNull('deleted_at');
    }
}
