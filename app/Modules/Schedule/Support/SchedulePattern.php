<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Support;

use Illuminate\Support\Carbon;

/**
 * When a standing order falls due. Two ways of saying it:
 *
 *  - `weekdays` — fixed days of the week, ISO-8601 (1 = Monday ... 7 = Sunday)
 *  - `intervalDays` — every N days counted from the start date
 *
 * Named weekdays win when both are given, because "Mon/Wed/Fri" and "every
 * 2 days" cannot both hold and the weekday list is the more specific wish.
 * Immutable, so nothing can move a schedule's goalposts mid-calculation.
 */
final readonly class SchedulePattern
{
    /**
     * @param  array<int, int>|null  $weekdays
     */
    public function __construct(
        public int $intervalDays,
        public ?array $weekdays,
        public Carbon $startDate,
        public ?Carbon $endDate = null,
    ) {}

    public function usesWeekdays(): bool
    {
        return $this->weekdays !== null && $this->weekdays !== [];
    }

    /**
     * Is a delivery due on this date?
     */
    public function matches(Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();

        if ($day->lt($this->startDate->copy()->startOfDay())) {
            return false;
        }

        if ($this->endDate !== null && $day->gt($this->endDate->copy()->startOfDay())) {
            return false;
        }

        if ($this->usesWeekdays()) {
            return in_array((int) $day->isoWeekday(), $this->weekdays, true);
        }

        $elapsed = (int) abs($this->startDate->copy()->startOfDay()->diffInDays($day));

        return $elapsed % max(1, $this->intervalDays) === 0;
    }

    /**
     * The first due date strictly after the one given. Returns null once the
     * schedule has run past its end date.
     */
    public function next(Carbon $after): ?Carbon
    {
        $candidate = $after->copy()->startOfDay()->addDay();

        if ($candidate->lt($this->startDate->copy()->startOfDay())) {
            $candidate = $this->startDate->copy()->startOfDay();

            if ($this->matches($candidate)) {
                return $candidate;
            }
        }

        // A weekday pattern needs at most a week of looking; an interval
        // pattern lands on the next multiple, so one interval's worth is the
        // ceiling. The cap keeps a nonsense pattern from spinning.
        $limit = $this->usesWeekdays() ? 7 : max(1, $this->intervalDays) + 1;

        for ($i = 0; $i < $limit; $i++) {
            if ($this->matches($candidate)) {
                return $candidate;
            }

            if ($this->endDate !== null && $candidate->gt($this->endDate->copy()->startOfDay())) {
                return null;
            }

            $candidate->addDay();
        }

        return null;
    }

    /**
     * The next $count due dates from $from inclusive — what the customer's
     * "My Deliveries" screen and the admin preview both list.
     *
     * @return array<int, Carbon>
     */
    public function upcoming(Carbon $from, int $count): array
    {
        $dates = [];
        $cursor = $from->copy()->startOfDay();

        if ($this->matches($cursor)) {
            $dates[] = $cursor->copy();
        }

        while (count($dates) < $count) {
            $cursor = $this->next($cursor);

            if ($cursor === null) {
                break;
            }

            $dates[] = $cursor->copy();
        }

        return $dates;
    }

    /**
     * The pattern in words, for the list column and the WhatsApp messages.
     */
    public function label(): string
    {
        if ($this->usesWeekdays()) {
            $names = array_map(
                static fn (int $day): string => Carbon::now()->startOfWeek()->addDays($day - 1)->format('D'),
                $this->weekdays,
            );

            return 'Every '.implode(', ', $names);
        }

        return match ($this->intervalDays) {
            1 => 'Daily',
            2 => 'Alternate days',
            7 => 'Weekly',
            default => "Every {$this->intervalDays} days",
        };
    }
}
