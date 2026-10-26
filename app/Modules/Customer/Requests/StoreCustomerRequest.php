<?php

declare(strict_types=1);

namespace App\Modules\Customer\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::CustomerManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            // Exactly ten digits: it is what the customer types to sign in, and
            // Notification\Support\PhoneNumber qualifies it to 91 before sending.
            'mobile' => ['required', 'string', 'digits:10', $this->uniqueMobile()],
            'password' => $this->passwordRules(),
            // Required because every booking falls back to it — see
            // Order\Services\CustomerAddressResolver. A customer with no address
            // on file produces an address-less order the driver cannot find.
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // Turned off for a standing-order customer: the office manages
            // their deliveries, so the app refuses a self-placed order.
            'can_self_order' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * Every rule answers in the owner's own words. Left to Laravel's defaults a
     * refusal reads "The mobile field must be 10 digits", which says nothing
     * about the number being a login.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Enter the first name.',
            'first_name.max' => 'The first name cannot be longer than 255 characters.',
            'last_name.max' => 'The last name cannot be longer than 255 characters.',

            'mobile.required' => 'Enter the mobile number — it is what the customer signs in with.',
            'mobile.digits' => 'The mobile number must be exactly 10 digits, with no spaces, dashes or 91 in front.',
            'mobile.unique' => 'That mobile number is already used by another account. Every account signs in with its own number.',

            'password.required' => 'Set an app password for the customer.',
            'password.confirmed' => 'The two passwords do not match.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.min.string' => 'The password must be at least 8 characters.',

            'address.required' => 'Enter the delivery address — every order the customer places falls back to it.',
            'address.max' => 'The delivery address cannot be longer than 1000 characters.',

            'latitude.numeric' => 'The latitude must be a number, for example 18.5204.',
            'latitude.between' => 'The latitude must be between -90 and 90.',
            'longitude.numeric' => 'The longitude must be a number, for example 73.8567.',
            'longitude.between' => 'The longitude must be between -180 and 180.',

            'can_self_order.required' => 'Choose whether this customer can place their own orders.',
            'can_self_order.boolean' => 'Choose whether this customer can place their own orders.',
            'status.required' => 'Choose whether this customer is active.',
            'status.boolean' => 'Choose whether this customer is active.',
        ];
    }

    /**
     * Names for anything the list above does not word itself.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'mobile' => 'mobile number',
            'password' => 'app password',
            'password_confirmation' => 'password confirmation',
            'address' => 'delivery address',
            'can_self_order' => 'can place own orders',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'confirmed', Password::min(8)];
    }

    /**
     * Deliberately NOT narrowed to `whereNull('deleted_at')`. The `users.mobile`
     * index is unconditional, so a rule that skipped trashed rows let the insert
     * through to the database and answered a 500 instead of a field error.
     */
    protected function uniqueMobile(): Unique
    {
        return Rule::unique('users', 'mobile');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => preg_replace('/\D/', '', (string) $this->input('mobile')),
            'can_self_order' => $this->boolean('can_self_order', true),
            'status' => $this->boolean('status'),
        ]);
    }
}
