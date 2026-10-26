<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreTankerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::TankerManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'vehicle_name' => ['required', 'string', 'max:255'],
            'vehicle_number' => ['required', 'string', 'max:30', $this->uniqueVehicleNumber()],
            'capacity_litres' => ['nullable', 'integer', 'min:1', 'max:200000'],
            // What one tanker-load from this vehicle costs the customer. Blank
            // leaves every order on that vehicle priced at 0 until a rate is set.
            'tanker_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'model_year' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:'.(date('Y') + 1)],
            'rc_number' => ['nullable', 'string', 'max:50'],
            'insurance_expiry' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function uniqueVehicleNumber(): Unique
    {
        return Rule::unique('tankers', 'vehicle_number')->whereNull('deleted_at');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vehicle_number' => strtoupper(trim((string) $this->input('vehicle_number'))),
            'status' => $this->boolean('status'),
        ]);
    }
}
