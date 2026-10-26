<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;

/**
 * What the customer pays. Water is sold by the tanker, not by the litre, so
 * there is exactly one unit in the system — a price per tanker-load — and the
 * whole model is:
 *
 *     rate × tanker_count = order_amount
 *
 * The rate has two scopes, an override and a default:
 *
 *   1. `tankers.tanker_rate`, the rate on the **vehicle that delivered it** —
 *      the owner's tankers are not all the same size, so a load from one is
 *      not worth a load from another;
 *   2. the `default_tanker_rate` setting, for a vehicle with no rate of its
 *      own.
 *
 * With neither set the amount is **0**, and that is deliberate: it reads as
 * "pricing is not configured yet" and sends the owner to the tanker's edit
 * screen. The old `default_order_amount` — a flat fee per delivery that
 * ignored `tanker_count` — was removed for exactly that reason; a setting that
 * silently switches off quantity is worse than no setting at all.
 *
 * The vehicle is unknown when an order is placed (the tanker comes from
 * whichever driver is assigned later), so a new order is priced from the
 * setting and re-priced from the vehicle at assignment — see
 * `OrderService::priceFor()`. An order whose amount was *given* rather than
 * calculated carries `amount_fixed` and is never re-priced.
 */
final class OrderPricing
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function amountFor(?int $tankerCount, ?Tanker $tanker = null): float
    {
        $count = $tankerCount === null || $tankerCount < 1 ? 1 : $tankerCount;

        return round($this->rateFor($tanker) * $count, 2);
    }

    /**
     * The price of one tanker-load: the vehicle's own rate when it has one,
     * otherwise the configured default. Zero when neither is set.
     */
    private function rateFor(?Tanker $tanker): float
    {
        $vehicleRate = (float) ($tanker?->tanker_rate ?? 0);

        if ($vehicleRate > 0) {
            return $vehicleRate;
        }

        return (float) ($this->settings->value('default_tanker_rate') ?? 0);
    }
}
