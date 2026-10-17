<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

/**
 * What a driver spent the money on. The single source of truth for the
 * categories — the forms build their dropdowns from here and the request rules
 * validate against the same list, so a category can never exist on one screen
 * and not the other.
 */
enum ExpenseCategoryEnum: string
{
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Maintenance = 'maintenance';
    case Food = 'food';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fuel => 'Fuel',
            self::Toll => 'Toll',
            self::Maintenance => 'Maintenance',
            self::Food => 'Food',
            self::Other => 'Other',
        };
    }

    /**
     * Cuba badge class for the category column.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Fuel => 'badge-light-primary',
            self::Toll => 'badge-light-info',
            self::Maintenance => 'badge-light-warning',
            self::Food => 'badge-light-secondary',
            self::Other => 'badge-light-dark',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Keyed value => label, the shape every select partial expects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
