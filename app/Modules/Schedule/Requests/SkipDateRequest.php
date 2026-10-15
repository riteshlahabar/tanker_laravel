<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Calling off one date. Authorisation is the policy's job (is this your
 * routine); the six-hour notice is SkipWindow's — this only checks the shape
 * of what was sent.
 */
final class SkipDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function date(): Carbon
    {
        return Carbon::parse((string) $this->input('date'))->startOfDay();
    }

    public function reason(): ?string
    {
        $reason = $this->string('reason')->trim()->value();

        return $reason === '' ? null : $reason;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'That date has already passed.',
        ];
    }
}
