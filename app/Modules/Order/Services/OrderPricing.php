<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Setting\Contracts\SettingRepositoryInterface;

/**
 * What the customer pays. The owner has not yet settled the rule, so for now
 * a per-litre rate is used when one is configured, otherwise the flat default
 * order amount. Changing the rule means changing only this class.
 */
final class OrderPricing
{
    public function __construct(private readonly SettingRepositoryInterface $settings) {}

    public function amountFor(?int $litres): float
    {
        $perLitre = (float) ($this->settings->value('price_per_litre') ?? 0);

        if ($perLitre > 0 && $litres !== null && $litres > 0) {
            return round($perLitre * $litres, 2);
        }

        return (float) ($this->settings->value('default_order_amount') ?? 0);
    }
}
