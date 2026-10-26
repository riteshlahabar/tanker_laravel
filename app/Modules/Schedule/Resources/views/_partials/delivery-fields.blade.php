{{-- What is delivered, when in the day, by whom, and at what rate — the same
     order of questions as the New Order form, so the two read alike. --}}
@php
    $rates = $driverRates ?? [];
@endphp

<div class="col-12">
    <h6 class="mt-2 mb-0">The delivery</h6>
    <hr class="mt-2">
</div>

<x-form.select name="delivery_slot_id" label="Delivery Window" :options="$slots"
    :value="$schedule?->delivery_slot_id" placeholder="— Choose a window —"
    help="The six-hour cancellation notice counts back from the window's start." />

<x-form.text name="delivery_time" label="Or Exact Time" type="time"
    :value="$schedule?->delivery_time ? \Illuminate\Support\Carbon::parse($schedule->delivery_time)->format('H:i') : null"
    help="Use this instead of a window." />

<x-form.textarea name="delivery_address" label="Delivery Address" :value="$schedule?->delivery_address" rows="2"
    width="col-md-6" placeholder="Leave empty to use the customer's address" />

<x-form.select name="preferred_driver_id" label="Usual Driver" :options="$drivers"
    :value="$schedule?->preferred_driver_id"
    help="Named here, each order is created already assigned to them — and priced from their tanker." />

<x-form.text name="tanker_rate" label="Rate per Tanker (₹)" type="number" :value="$schedule?->tanker_rate"
    help="Filled from the usual driver's tanker. Leave empty to price every delivery from whichever tanker goes out."
    :attrs="['step' => '0.01', 'min' => 0]" />

<x-form.text name="tanker_count" label="Tankers per Delivery" type="number"
    :value="$schedule?->tanker_count ?? 1" :attrs="['min' => 1, 'max' => 20]" />

<div class="col-md-6">
    <label class="form-label">Amount per Delivery</label>
    <div class="form-control-plaintext f-w-600 f-18" id="schedule-total">₹ 0.00</div>
    <small class="f-light" id="schedule-total-note">Rate per tanker × tankers per delivery.</small>
</div>
