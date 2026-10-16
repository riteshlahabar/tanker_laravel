<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Setting\Contracts\SettingRepositoryInterface;
use App\Modules\Tanker\Models\Tanker;

/**
 * What the customer pays. Water is sold by the tanker, not by the litre, so
 * the figure is a price per tanker-load multiplied by how many were ordered.
 *
 * Three sources, in order:
 *
 *   1. the rate on the **vehicle that delivered it** — the owner's tankers are
 *      not all the same size, so a load from one is not worth a load from
 *      another;
 *   2. the `price_per_tanker` setting, for a vehicle with no rate of its own;
 *   3. the flat `default_order_amount`, when neither is set.
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

        $rate = $this->rateFor($tanker);

        if ($rate > 0) {
            return round($rate * $count, 2);
        }

        return (float) ($this->settings->value('default_order_amount') ?? 0);
    }

    /**
     * The price of one tanker-load: the vehicle's own rate when it has one,
     * otherwise the configured default.
     */
    private function rateFor(?Tanker $tanker): float
    {
        $vehicleRate = (float) ($tanker?->tanker_rate ?? 0);

        if ($vehicleRate > 0) {
            return $vehicleRate;
        }

        return (float) ($this->settings->value('price_per_tanker') ?? 0);
    }
}
