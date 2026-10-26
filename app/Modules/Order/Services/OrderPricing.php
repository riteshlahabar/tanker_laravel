<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Tanker\Models\Tanker;

/**
 * What the customer pays. Water is sold by the tanker-load, never by the
 * litre, so there is exactly one unit of price in the system — a rate per
 * tanker — and the whole model is one line:
 *
 *     tanker_rate × tanker_count = order_amount
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
 * An order whose rate was *given* rather than read off the vehicle — the admin
 * typing a different rate on the order form — holds `rate_fixed` and is never
 * re-priced.
 */
final class OrderPricing
{
    /**
     * Both halves of the rule, so a caller can never store a total that
     * disagrees with the rate beside it.
     *
     * @return array{tanker_rate: float, order_amount: float}
     */
    public function priceOn(float $rate, ?int $tankerCount): array
    {
        return [
            'tanker_rate' => round($rate, 2),
            'order_amount' => round($rate * $this->countFor($tankerCount), 2),
        ];
    }

    /**
     * The same, priced from a vehicle rather than a typed figure.
     *
     * @return array{tanker_rate: float, order_amount: float}
     */
    public function priceFrom(?Tanker $tanker, ?int $tankerCount): array
    {
        return $this->priceOn($this->rateFor($tanker), $tankerCount);
    }

    public function amountFor(?int $tankerCount, ?Tanker $tanker = null): float
    {
        return $this->priceFrom($tanker, $tankerCount)['order_amount'];
    }

    /**
     * What one tanker-load costs on a given rate — the figure the order form
     * shows the moment a driver is picked, and the basis of the total.
     */
    public function amountForRate(float $rate, ?int $tankerCount): float
    {
        return $this->priceOn($rate, $tankerCount)['order_amount'];
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
