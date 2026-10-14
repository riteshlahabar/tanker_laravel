<?php

declare(strict_types=1);

namespace App\Modules\Report\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The filter set every report understands. Immutable, so a report cannot
 * quietly change what it was asked for.
 */
final readonly class ReportFilters
{
    public function __construct(
        public string $from,
        public string $to,
        public ?int $driverId = null,
        public ?int $customerId = null,
        public ?int $spotId = null,
        public ?int $tankerId = null,
        public ?string $search = null,
    ) {}

    /**
     * Defaults to the current month when the user has not chosen a range.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            from: $request->date('from')?->toDateString() ?? Carbon::now()->startOfMonth()->toDateString(),
            to: $request->date('to')?->toDateString() ?? Carbon::now()->toDateString(),
            driverId: self::intOrNull($request, 'driver_id'),
            customerId: self::intOrNull($request, 'customer_id'),
            spotId: self::intOrNull($request, 'spot_id'),
            tankerId: self::intOrNull($request, 'tanker_id'),
            search: $request->string('search')->value() ?: null,
        );
    }

    public static function forDay(string $date): self
    {
        return new self(from: $date, to: $date);
    }

    public function fromDateTime(): string
    {
        return $this->from.' 00:00:00';
    }

    public function toDateTime(): string
    {
        return $this->to.' 23:59:59';
    }

    public function label(): string
    {
        return $this->from === $this->to
            ? Carbon::parse($this->from)->format('d M Y')
            : Carbon::parse($this->from)->format('d M Y').' — '.Carbon::parse($this->to)->format('d M Y');
    }

    /**
     * @return array<string, mixed>
     */
    public function toQueryString(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => $this->to,
            'driver_id' => $this->driverId,
            'customer_id' => $this->customerId,
            'spot_id' => $this->spotId,
            'tanker_id' => $this->tankerId,
            'search' => $this->search,
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    private static function intOrNull(Request $request, string $key): ?int
    {
        $value = $request->input($key);

        return $value === null || $value === '' ? null : (int) $value;
    }
}
