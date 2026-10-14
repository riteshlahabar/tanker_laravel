<?php

declare(strict_types=1);

namespace App\Modules\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelOrderRequest extends FormRequest
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
            'cancel_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
