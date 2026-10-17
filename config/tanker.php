<?php

declare(strict_types=1);

/*
 * Project defaults. Values the owner can change at runtime live in the
 * `settings` table instead; these are the fallbacks.
 */

return [
    'default_monthly_salary' => env('TANKER_DEFAULT_MONTHLY_SALARY', 15000),
    'currency_symbol' => '₹',
];
