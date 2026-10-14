<?php

declare(strict_types=1);

namespace App\Modules\Report\Support;

/**
 * Reports hand back raw numbers so the spreadsheet gets real values; the
 * screen formats them here instead.
 */
final class CellFormat
{
    /**
     * @var array<int, string>
     */
    private const MONEY_HEADINGS = [
        'Order Value', 'Refill Cost', 'Earning', 'Revenue', 'Amount Paid',
        'Charge per Refill', 'Total Refill Cost', 'Driver Earning',
    ];

    public static function render(string $heading, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (in_array($heading, self::MONEY_HEADINGS, true)) {
            return '₹ '.number_format((float) $value, 2);
        }

        return is_int($value) || is_float($value)
            ? number_format((float) $value)
            : (string) $value;
    }
}
