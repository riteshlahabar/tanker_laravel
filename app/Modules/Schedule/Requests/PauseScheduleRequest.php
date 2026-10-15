<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Requests;

use App\Modules\Core\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

final class PauseScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::ScheduleManage->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Omitted means paused indefinitely, until someone resumes it.
            'pause_until' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function pauseUntil(): ?Carbon
    {
        $until = $this->input('pause_until');

        return is_string($until) && $until !== '' ? Carbon::parse($until)->startOfDay() : null;
    }
}
