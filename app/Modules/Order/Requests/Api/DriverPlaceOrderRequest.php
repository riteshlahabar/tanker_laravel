<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests\Api;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A driver writing down a job they have just been given on the route.
 *
 * Shaped on [\App\Modules\Order\Requests\StoreOrderRequest], the admin's, with
 * **one deliberate omission: there is no `driver_id` rule.** The order always
 * goes to the driver holding the token — [driverId()] returns their own id — so
 * a `driver_id` in the body is not validated, not read and cannot be honoured.
 * A driver therefore cannot place work onto a colleague, whatever they post.
 *
 * That is the same device [PlaceOrderRequest] uses to force `customer_id` from
 * a customer's token: the field that decides whose record this is never comes
 * from the request.
 *
 * The rate **is** accepted, at the owner's decision — drivers negotiate on the
 * road — and a rate differing from the vehicle's sets `rate_fixed` through
 * `OrderService::applyTypedRate()`, exactly as on the admin form.
 */
final class DriverPlaceOrderRequest extends FormRequest
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
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_slot_id' => [
                'nullable', 'integer',
                Rule::exists('delivery_slots', 'id')->whereNull('deleted_at'),
            ],
            // Where this load is drawn from. Optional, because it is usually
            // decided on the route — the refill screen asks again if it was
            // left out here.
            'spot_id' => [
                'nullable', 'integer',
                Rule::exists('spots', 'id')->whereNull('deleted_at'),
            ],
            'tanker_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'tanker_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The order's own columns. `spot_id` and `tanker_rate` are not among them:
     * both are applied by assignment, which snapshots the filling point's
     * refill charge and decides what the total becomes.
     *
     * @return array<string, mixed>
     */
    public function orderData(): array
    {
        return collect($this->validated())
            ->except(['spot_id', 'tanker_rate'])
            ->all();
    }

    /**
     * The one driver this order can possibly go to.
     */
    public function driverId(): int
    {
        return (int) $this->user()->id;
    }

    public function spotId(): ?int
    {
        $spot = $this->validated('spot_id');

        return $spot === null || $spot === '' ? null : (int) $spot;
    }

    public function tankerRate(): ?float
    {
        $rate = $this->validated('tanker_rate');

        return $rate === null || $rate === '' ? null : (float) $rate;
    }

    /**
     * Guards against a driver pointing an order at another driver's — or their
     * own — user id in the customer field.
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
