<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handing an order to a different driver. Same shape as AssignOrderRequest
 * plus a reason, and it carries the same permission — choosing who delivers
 * is one job whether it is the first choice or a replacement.
 */
final class ReassignOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::OrderAssign->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'driver_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                $this->mustBeDriver(),
            ],
            'spot_id' => ['nullable', 'integer', Rule::exists('spots', 'id')->whereNull('deleted_at')],
            // Free text, because the useful reasons are not a fixed list
            // ("absent", "vehicle breakdown", "closer to the route").
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function mustBeDriver(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $isDriver = User::query()
                ->whereKey($value)
                ->role(RoleEnum::Driver->value)
                ->exists();

            if (! $isDriver) {
                $fail('The selected record is not a driver.');
            }
        };
    }
}
