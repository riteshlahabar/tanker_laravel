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
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            // Naming a window is what gives the order a concrete delivery_at.
            'delivery_slot_id' => [
                'nullable', 'integer',
                Rule::exists('delivery_slots', 'id')->whereNull('deleted_at'),
            ],
            // The driver is chosen here, not on a later screen: the price of
            // the order is the rate of the tanker they hold, so there is no
            // amount to show until one is picked.
            'driver_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            // The filling point this load is drawn from. Optional: it is often
            // decided on the route, which is what the Assign screen allows too.
            'spot_id' => [
                'nullable', 'integer',
                Rule::exists('spots', 'id')->whereNull('deleted_at'),
            ],
            'tanker_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            // Pre-filled from that driver's tanker and editable, for the day a
            // load is sold at something other than the vehicle's usual rate.
            'tanker_rate' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The order's own columns. `driver_id`, `spot_id` and `tanker_rate` are
     * not among them: all three are applied by assignment, which snapshots the
     * tanker and the filling point's refill charge, and the rate decides what
     * the total becomes.
     *
     * There is no amount field to carry — an admin-entered order is priced the
     * same way every other order is, from the rate per tanker.
     *
     * @return array<string, mixed>
     */
    public function orderData(): array
    {
        return collect($this->validated())
            ->except(['driver_id', 'spot_id', 'tanker_rate'])
            ->all();
    }

    public function driverId(): int
    {
        return (int) $this->validated('driver_id');
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
