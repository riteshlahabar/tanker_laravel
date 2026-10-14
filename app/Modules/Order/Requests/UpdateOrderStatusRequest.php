<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use App\Modules\Core\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatusEnum::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function status(): OrderStatusEnum
    {
        return OrderStatusEnum::from($this->string('status')->value());
    }
}
