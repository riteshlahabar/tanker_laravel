<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::OrderCreate->value) ?? false;
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
                $this->mustBeCustomer(),
            ],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_slot' => ['nullable', 'string', 'max:50'],
            // Naming a window is what gives the order a concrete delivery_at.
            'delivery_slot_id' => [
                'nullable', 'integer',
                Rule::exists('delivery_slots', 'id')->whereNull('deleted_at'),
            ],
            'tanker_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'order_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The admin types the amount by hand, so it is the figure of record:
     * `amount_fixed` stops assignment re-pricing it from the vehicle's rate.
     *
     * @return array<string, mixed>
     */
    public function orderData(): array
    {
        return [
            ...$this->validated(),
            'amount_fixed' => true,
        ];
    }

    /**
     * Guards against an admin pointing an order at a driver's user id.
     */
    private function mustBeCustomer(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $isCustomer = User::query()
                ->whereKey($value)
                ->role(RoleEnum::Customer->value)
                ->exists();

            if (! $isCustomer) {
                $fail('The selected record is not a customer.');
            }
        };
    }
}
