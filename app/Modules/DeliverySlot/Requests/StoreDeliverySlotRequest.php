<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverySlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SlotManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->boolean('status'),
            'sort_order' => (int) ($this->input('sort_order') ?: 0),
        ]);
    }
}
