@php
    $driverOptions = $drivers->mapWithKeys(fn($d) => [
        $d->id => $d->full_name . ' — ' . ($d->currentAssignment?->tanker?->label() ?? 'no tanker'),
    ])->all();

    $spotOptions = $spots->mapWithKeys(fn($s) => [
        $s->id => $s->name . ' (₹ ' . number_format((float) $s->refill_charge, 2) . ')',
    ])->all();
@endphp

<x-form.select name="driver_id" label="Driver" :options="$driverOptions" placeholder="Select a driver"
    width="col-12" help="The driver's current tanker is used automatically." required />

<x-form.select name="spot_id" label="Filling Point" :options="$spotOptions"
    placeholder="— Decide on the route —" width="col-12"
    help="Only available filling points are listed. Its refill charge is saved onto the order." />

<x-form.actions :cancel="route('admin.order.show', $order)" label="Assign Order" />
