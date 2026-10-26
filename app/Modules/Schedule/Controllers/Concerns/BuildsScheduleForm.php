<?php

declare(strict_types=1);

namespace App\Modules\Schedule\Controllers\Concerns;

use App\Models\User;
use App\Modules\Core\Enums\RoleEnum;
use App\Modules\Customer\Contracts\CustomerRepositoryInterface;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;

/**
 * The dropdowns the create and edit forms both need. Shared so neither
 * controller carries the queries and both stay a handful of lines.
 */
trait BuildsScheduleForm
{
    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function formOptions(): array
    {
        $drivers = User::query()
            ->active()
            ->role(RoleEnum::Driver->value)
            ->with('currentAssignment.tanker:id,tanker_rate')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return [
            'customers' => app(CustomerRepositoryInterface::class)->scopedQuery()
                ->where('status', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'mobile', 'address'])
                ->mapWithKeys(fn (User $c): array => [
                    $c->id => $c->full_name.' — '.$c->mobile,
                ])
                ->all(),

            'slots' => ['' => 'Use an exact time instead']
                + app(DeliverySlotRepositoryInterface::class)->options(),

            'drivers' => ['' => 'Leave for the admin to assign']
                + $drivers
                    ->mapWithKeys(fn (User $d): array => [$d->id => $d->full_name])
                    ->all(),

            // What each driver's current tanker charges for a load, so the
            // form can fill the rate in the moment one is chosen — the same
            // behaviour as the New Order screen.
            'driverRates' => $drivers
                ->mapWithKeys(fn (User $d): array => [
                    $d->id => (float) ($d->currentAssignment?->tanker?->tanker_rate ?? 0),
                ])
                ->all(),
        ];
    }
}
