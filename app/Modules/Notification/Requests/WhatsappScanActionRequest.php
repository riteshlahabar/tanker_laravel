<?php

declare(strict_types=1);

namespace App\Modules\Notification\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WhatsappScanActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SettingManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['restart', 'logout'])],
        ];
    }

    public function isLogout(): bool
    {
        return $this->input('action') === 'logout';
    }
}
