<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Support;

use App\Modules\Core\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Schedule\Exceptions\ScheduleSkipException;
use Illuminate\Support\Carbon;

/**
 * May this delivery still be called off? Two rules, and whichever bites first
 * wins:
 *
 *  1. **Six hours' notice.** A customer may skip only while
 *     `now() <= delivery_at - 6 hours`. The admin overrides this.
 *  2. **Status backstop.** Nobody may skip once the order reaches `refilling`,
 *     `out_for_delivery` or `delivered` — the water has already been bought
 *     for that customer, so a late skip costs the owner real money.
 *
 * Both the API's `can_skip` flag and the service's refusal read this class, so
 * the button the app draws and the answer the server gives cannot disagree.
 */
final class SkipWindow
{
    public const NOTICE_HOURS = 6;

    /**
     * Statuses past the point of no return.
     *
     * @var array<int, OrderStatusEnum>
     */
    private const UNDERWAY = [
        OrderStatusEnum::Refilling,
        OrderStatusEnum::OutForDelivery,
        OrderStatusEnum::Delivered,
    ];

    /**
     * @param  Carbon  $dueAt  the order's resolved `delivery_at`, or the same
     *                         moment worked out from the schedule when no
     *                         order exists yet
     */
    public function allows(Carbon $dueAt, ?Order $order, bool $asAdmin): bool
    {
        return $this->refusal($dueAt, $order, $asAdmin) === null;
    }

    /**
     * Why not, or null when it is allowed. Returning the reason rather than a
     * bare false is what lets the app explain itself.
     */
    public function refusal(Carbon $dueAt, ?Order $order, bool $asAdmin): ?ScheduleSkipException
    {
        // Rule 2 first: it binds the admin too, so there is no point checking
        // notice on an order that is already being filled.
        if ($order !== null && in_array($order->status, self::UNDERWAY, true)) {
            return ScheduleSkipException::alreadyUnderway();
        }

        if ($asAdmin) {
            return null;
        }

        if (Carbon::now()->gt($dueAt->copy()->subHours(self::NOTICE_HOURS))) {
            return ScheduleSkipException::tooLate(self::NOTICE_HOURS);
        }

        return null;
    }

    public function guard(Carbon $dueAt, ?Order $order, bool $asAdmin): void
    {
        $refusal = $this->refusal($dueAt, $order, $asAdmin);

        if ($refusal !== null) {
            throw $refusal;
        }
    }

    /**
     * The deadline, so a screen can show "call off before 2:00 AM" rather
     * than making the customer work it out.
     */
    public function deadlineFor(Carbon $dueAt): Carbon
    {
        return $dueAt->copy()->subHours(self::NOTICE_HOURS);
    }
}
