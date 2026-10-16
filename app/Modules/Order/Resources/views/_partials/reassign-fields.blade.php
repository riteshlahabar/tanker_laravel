@php
    $driverOptions = $drivers->mapWithKeys(fn($d) => [
        $d->id => $d->full_name . ' — ' . ($d->currentAssignment?->tanker?->label() ?? 'no tanker'),
    ])->all();

    $spotOptions = $spots->mapWithKeys(fn($s) => [
        $s->id => $s->name . ' (₹ ' . number_format((float) $s->refill_charge, 2) . ')',
    ])->all();
@endphp

<div class="col-12">
    <div class="alert alert-light-primary" role="alert">
        <p class="mb-0">
            Currently with <strong>{{ $order->driver?->full_name ?? '—' }}</strong>
            on {{ $order->tanker?->label() ?? 'no tanker' }}. They will be told the order has moved.
            @if ($order->delivery_schedule_id)
                This is a standing-order delivery — only <strong>this day</strong> changes, the routine
                keeps its usual driver.
            @endif
        </p>
    </div>
</div>

<x-form.select name="driver_id" label="New Driver" :options="$driverOptions" placeholder="Select a driver"
    width="col-12" help="The new driver's current tanker and per-delivery rate are used." required />

<x-form.select name="spot_id" label="Refill Spot" :options="$spotOptions"
    placeholder="— Keep the current spot —" width="col-12"
    help="Leave this alone unless the new driver should fill somewhere else." />

<x-form.textarea name="reason" label="Reason" rows="2"
    placeholder="Driver absent" />

<x-form.actions :cancel="route('admin.order.show', $order)" label="Change Driver" />
