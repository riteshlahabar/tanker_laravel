<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Support;

use App\Modules\Order\Models\Order;
use Illuminate\Support\Carbon;

/**
 * One date on a customer's routine, as the "My Deliveries" list shows it.
 *
 * `canSkip` is resolved on the server so the app disables the button rather
 * than letting the tap fail, and `skipReason` explains a disabled one.
 */
final readonly class UpcomingDate
{
    public function __construct(
        public Carbon $date,
        public Carbon $dueAt,
        public ?Order $order,
        public bool $skipped,
        public bool $canSkip,
        public ?string $skipReason = null,
        public ?string $cancelledReason = null,
    ) {}

    public function orderId(): ?int
    {
        return $this->order?->id;
    }

    public function status(): ?string
    {
        return $this->order?->status->value;
    }

    public function statusLabel(): ?string
    {
        return $this->order?->status->label();
    }
}
