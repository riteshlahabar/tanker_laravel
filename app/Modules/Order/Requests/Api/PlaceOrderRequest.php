<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests\Api;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A customer ordering from the app. The customer is taken from the token, not
 * the body, so one customer can never place an order as another.
 */
final class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan(PermissionEnum::OrderCreate->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'delivery_address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_slot' => ['nullable', 'string', 'max:50'],
            'quantity_litres' => ['nullable', 'integer', 'min:1', 'max:200000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderData(float $amount): array
    {
        return [
            ...$this->validated(),
            'customer_id' => $this->user()->id,
            'order_amount' => $amount,
        ];
    }
}
