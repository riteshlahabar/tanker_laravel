<?php

declare(strict_types=1);

namespace App\Modules\Driver\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeTankerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::DriverManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tanker_id' => ['nullable', 'integer', Rule::exists('tankers', 'id')->whereNull('deleted_at')],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
