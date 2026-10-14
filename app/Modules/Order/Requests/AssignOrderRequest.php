<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Models\User;
use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignOrderRequest extends FormRequest
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
