{{-- What is delivered, when in the day, and by whom. --}}
<div class="col-12">
    <h6 class="mt-2 mb-0">The delivery</h6>
    <hr class="mt-2">
</div>

<x-form.select name="delivery_slot_id" label="Delivery Window" :options="$slots"
    :value="$schedule?->delivery_slot_id"
    help="The six-hour cancellation notice counts back from the window's start." />

<x-form.text name="delivery_time" label="Or Exact Time" type="time"
    :value="$schedule?->delivery_time ? \Illuminate\Support\Carbon::parse($schedule->delivery_time)->format('H:i') : null"
    help="Use this instead of a window." />

<x-form.text name="quantity_litres" label="Litres per Delivery" type="number"
    :value="$schedule?->quantity_litres" :attrs="['min' => 1, 'max' => 200000]" />

<x-form.text name="order_amount" label="Fixed Amount" type="number" :value="$schedule?->order_amount"
    :attrs="['step' => '0.01', 'min' => 0]" help="Leave empty to use the usual pricing rule." />

<x-form.select name="preferred_driver_id" label="Usual Driver" :options="$drivers"
    :value="$schedule?->preferred_driver_id"
    help="Named here, each order is created already assigned to them." />

<x-form.textarea name="delivery_address" label="Delivery Address" :value="$schedule?->delivery_address" rows="2"
    placeholder="Leave empty to use the customer's address" />

<x-form.text name="latitude" label="Latitude" :value="$schedule?->latitude" :attrs="['step' => 'any']" />

<x-form.text name="longitude" label="Longitude" :value="$schedule?->longitude" :attrs="['step' => 'any']" />
