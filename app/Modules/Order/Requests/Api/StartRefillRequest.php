<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The driver says which spot they are filling at. If the admin already chose
 * one, the driver may still override it — they are the one on the route.
 */
final class StartRefillRequest extends FormRequest
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
            'spot_id' => ['nullable', 'integer', Rule::exists('spots', 'id')->whereNull('deleted_at')],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
