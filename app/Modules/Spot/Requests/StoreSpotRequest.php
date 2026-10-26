<?php

declare(strict_types=1);

namespace App\Modules\Spot\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SpotManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // All three are required now. A filling point the driver cannot
            // find, or whose keeper cannot be rung when it is shut or queued,
            // is not usable on the route — and the coordinates that used to
            // stand in for an address are gone, so the address is the only
            // thing that says where this is.
            'address' => ['required', 'string', 'max:1000'],
            'refill_charge' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'water_type' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\- ]+$/'],
            'is_available' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * In the owner's own words, so a refusal says why the field matters.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name the filling point.',
            'address.required' => 'Enter the address — it is how the driver finds this point.',
            'refill_charge.required' => 'Enter what it costs to fill a tanker here.',
            'contact_person.required' => 'Enter the contact person at this filling point.',
            'contact_mobile.required' => 'Enter a contact mobile — the driver rings it if the point is shut.',
            'contact_mobile.regex' => 'The contact mobile may contain only digits, spaces, + and -.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_available' => $this->boolean('is_available'),
            'status' => $this->boolean('status'),
        ]);
    }
}
