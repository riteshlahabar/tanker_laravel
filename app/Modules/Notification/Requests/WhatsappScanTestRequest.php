<?php

declare(strict_types=1);

namespace App\Modules\Notification\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

final class WhatsappScanTestRequest extends FormRequest
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
            // Ten national digits, or twelve with the country code already on
            // the front — PhoneNumber settles which before sending.
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter the number as digits only, e.g. 9812345678.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => preg_replace('/\D/', '', (string) $this->input('phone'))]);
    }

    public function phone(): string
    {
        return (string) $this->input('phone');
    }

    public function body(): string
    {
        $message = trim((string) $this->input('message'));

        return $message !== ''
            ? $message
            : config('app.name').': WhatsApp is connected. This is a test message.';
    }
}
