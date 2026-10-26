@php
    $driverOptions = $drivers->mapWithKeys(fn($d) => [
        $d->id => $d->full_name . ' — ' . ($d->currentAssignment?->tanker?->label() ?? 'no tanker'),
    ])->all();
@endphp

<x-form.select name="customer_id" label="Customer"
    :options="$customers->mapWithKeys(fn($c) => [$c->id => $c->full_name . ' — ' . $c->mobile])->all()"
    placeholder="Select a customer" required />

<x-form.text name="delivery_date" label="Delivery Date" type="date"
    :value="old('delivery_date', now()->toDateString())" :attrs="['min' => now()->toDateString()]" required />

<x-form.textarea name="delivery_address" label="Delivery Address" rows="2" required />

<x-form.select name="delivery_slot_id" label="Delivery Slot" :options="$slots"
    placeholder="— No particular window —"
    help="Sets the delivery time the six-hour skip rule counts back from." />

<x-form.select name="driver_id" label="Driver" :options="$driverOptions" placeholder="Select a driver"
    help="Only active drivers holding a tanker are listed. The order is assigned to them straight away."
    required />

<x-form.text name="tanker_rate" label="Rate per Tanker (₹)" type="number" :value="old('tanker_rate')"
    help="Filled from the chosen driver's tanker. Change it only to sell this load at a different price."
    :attrs="['step' => '0.01', 'min' => 0]" />

<x-form.text name="tanker_count" label="Number of Tankers" type="number"
    :value="old('tanker_count', 1)" help="How many tanker-loads this delivery is for."
    :attrs="['min' => 1, 'max' => 20]" />

<div class="col-md-6">
    <label class="form-label">Total Amount</label>
    <div class="form-control-plaintext f-w-600 f-18" id="order-total">₹ 0.00</div>
    <small class="f-light" id="order-total-note">Rate per tanker × number of tankers.</small>
</div>

<x-form.textarea name="notes" label="Notes" rows="2" />

<x-form.actions :cancel="route('admin.order.index')" label="Place Order" />
