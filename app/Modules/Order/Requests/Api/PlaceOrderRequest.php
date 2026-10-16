<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests\Api;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A customer ordering from the app. The customer is taken from the token, not
 * the body, so one customer can never place an order as another.
 */
final class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // A standing-order customer has can_self_order turned off: their
        // deliveries are the admin's to manage, so the app refuses the order
        // rather than quietly creating one beside the routine. They can still
        // see and track everything the schedule produces.
        return ($user?->tokenCan(PermissionEnum::OrderCreate->value) ?? false)
            && $user->canSelfOrder();
    }

    /**
     * Said plainly, because a generic "not allowed" would read as a bug to a
     * customer who can see their own deliveries listed.
     */
    protected function failedAuthorization(): void
    {
        $reason = ($this->user()?->canSelfOrder() ?? true)
            ? __('This action is not allowed.')
            : __('Your deliveries are on a fixed routine managed by the office. Please ring them to change an order.');

        throw new AuthorizationException($reason);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Optional: left out, the customer's own address — the one entered
            // when the admin added them — is used. See CustomerAddressResolver.
            'delivery_address' => ['nullable', 'string', 'max:1000'],
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
            // Calculated, not given — so assignment is free to re-price it
            // from the tanker that actually goes out.
            'amount_fixed' => false,
        ];
    }
}
