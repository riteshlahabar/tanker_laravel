<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Requests;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::ScheduleManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                $this->mustHoldRole(RoleEnum::Customer, 'customer'),
            ],

            'interval_days' => ['required', 'integer', 'min:1', 'max:30'],
            // Optional named days; when given they win over the interval.
            'weekdays' => ['nullable', 'array', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7'],

            'tanker_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'order_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],

            // One or the other must be named: the six-hour notice has to count
            // back from something concrete.
            'delivery_slot_id' => [
                'nullable', 'required_without:delivery_time', 'integer',
                Rule::exists('delivery_slots', 'id')->whereNull('deleted_at'),
            ],
            'delivery_time' => ['nullable', 'required_without:delivery_slot_id', 'date_format:H:i'],

            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'preferred_driver_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                $this->mustHoldRole(RoleEnum::Driver, 'driver'),
            ],

            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status', true),
            // An empty multi-select arrives as [''] or ''; both mean "use the
            // interval instead".
            'weekdays' => $this->filledWeekdays(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivery_slot_id.required_without' => 'Choose a delivery window, or set an exact time.',
            'delivery_time.required_without' => 'Set an exact time, or choose a delivery window.',
        ];
    }

    /**
     * @return array<int, int>|null
     */
    private function filledWeekdays(): ?array
    {
        $days = array_filter(
            (array) $this->input('weekdays', []),
            static fn ($day): bool => $day !== null && $day !== '',
        );

        return $days === [] ? null : array_values(array_map('intval', $days));
    }

    /**
     * Guards against a schedule pointing at a driver's id as its customer, or
     * a customer's id as its driver.
     */
    private function mustHoldRole(RoleEnum $role, string $label): callable
    {
        return function (string $attribute, mixed $value, callable $fail) use ($role, $label): void {
            $holds = User::query()->whereKey($value)->role($role->value)->exists();

            if (! $holds) {
                $fail("The selected record is not a {$label}.");
            }
        };
    }
}
