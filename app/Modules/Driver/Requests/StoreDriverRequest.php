<?php

declare(strict_types=1);

namespace App\Modules\Driver\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class StoreDriverRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9]{10,15}$/', $this->uniqueMobile()],
            'password' => $this->passwordRules(),
            'tanker_id' => ['nullable', 'integer', Rule::exists('tankers', 'id')->whereNull('deleted_at')],
            'monthly_salary' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(8)];
    }

    protected function uniqueMobile(): Unique
    {
        return Rule::unique('users', 'mobile')->whereNull('deleted_at');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => preg_replace('/\D/', '', (string) $this->input('mobile')),
            'status' => $this->boolean('status'),
        ]);
    }
}
