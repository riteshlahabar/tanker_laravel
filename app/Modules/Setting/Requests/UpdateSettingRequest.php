<?php

declare(strict_types=1);

namespace App\Modules\Setting\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'admin_whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{10,15}$/'],
            'default_driver_rate' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'default_order_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'price_per_tanker' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'daily_report_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_whatsapp_number.regex' => 'Enter the number with country code and no symbols, e.g. 919812345678.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'admin_whatsapp_number' => preg_replace('/\D/', '', (string) $this->input('admin_whatsapp_number')),
        ]);
    }
}
