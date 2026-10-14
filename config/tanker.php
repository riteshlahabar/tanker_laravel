<?php

declare(strict_types=1);

/*
 * Project defaults. Values the owner can change at runtime live in the
 * `settings` table instead; these are the fallbacks.
 */

return [
    'default_driver_rate' => env('TANKER_DEFAULT_DRIVER_RATE', 150),
    'currency_symbol' => '₹',
];
