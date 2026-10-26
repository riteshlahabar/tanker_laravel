<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Tanker\Models\Tanker;

/**
 * What the customer pays. Water is sold by the tanker, not by the litre, so
 * there is exactly one unit in the system — a price per tanker-load — and the
 * whole model is:
 *
 *     rate × tanker_count = order_amount
 *
 * The rate comes from **one place only**: `tankers.tanker_rate`, the rate on
 * the vehicle that delivers. There is no global default — the owner's tankers
 * are not the same size, so a single fallback figure would be wrong for every
 * vehicle that has a rate of its own and invented for every vehicle that does
 * not.
 *
 * With no vehicle, or a vehicle with no rate, the amount is **0**, and that is
 * deliberate: it reads as "no tanker has been chosen yet, so the price is not
 * known". An order placed from the customer app is in exactly that state until
 * an admin assigns a driver, at which point `OrderService::assign()` prices it
 * from the vehicle that actually goes out.
 *
 * An order whose amount was *given* rather than calculated — the admin typing
 * a rate on the order form, or a standing order carrying its own figure —
 * holds `amount_fixed` and is never re-priced.
 */
final class OrderPricing
{
    public function amountFor(?int $tankerCount, ?Tanker $tanker = null): float
    {
        return round($this->rateFor($tanker) * $this->countFor($tankerCount), 2);
    }

    /**
     * What one tanker-load costs on a given rate — the figure the order form
     * shows the moment a driver is picked, and the basis of the total.
     */
    public function amountForRate(float $rate, ?int $tankerCount): float
    {
        return round($rate * $this->countFor($tankerCount), 2);
    }

    public function rateFor(?Tanker $tanker): float
    {
        return (float) ($tanker?->tanker_rate ?? 0);
    }

    private function countFor(?int $tankerCount): int
    {
        return $tankerCount === null || $tankerCount < 1 ? 1 : $tankerCount;
    }
}
