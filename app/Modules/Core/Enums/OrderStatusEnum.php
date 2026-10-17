<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

enum OrderStatusEnum: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Refilling = 'refilling';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::Refilling => 'Refilling at Spot',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Cuba badge class for the status column.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'badge-light-warning',
            self::Assigned => 'badge-light-info',
            self::Refilling => 'badge-light-primary',
            self::OutForDelivery => 'badge-light-secondary',
            self::Delivered => 'badge-light-success',
            self::Cancelled => 'badge-light-danger',
        };
    }

    /**
     * Statuses this one may move to. The single source of truth for the
     * lifecycle — OrderService refuses anything not listed here.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Refilling, self::Cancelled],
            self::Refilling => [self::OutForDelivery, self::Cancelled],
            self::OutForDelivery => [self::Delivered, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /**
     * The wire values of every status that still needs work doing — what the
     * dashboard's open-orders list is scoped to. Derived from [isOpen] rather
     * than listed again, so adding a status cannot leave the two disagreeing.
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isOpen()),
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
