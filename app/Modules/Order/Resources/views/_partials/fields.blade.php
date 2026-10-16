<x-form.select name="customer_id" label="Customer"
    :options="$customers->mapWithKeys(fn($c) => [$c->id => $c->full_name . ' — ' . $c->mobile])->all()"
    placeholder="Select a customer" required />

<x-form.text name="delivery_date" label="Delivery Date" type="date"
    :value="old('delivery_date', now()->toDateString())" :attrs="['min' => now()->toDateString()]" required />

<x-form.textarea name="delivery_address" label="Delivery Address" rows="2" required />

<x-form.text name="tanker_count" label="Number of Tankers" type="number"
    :value="old('tanker_count', 1)" help="How many tanker-loads this delivery is for."
    :attrs="['min' => 1, 'max' => 20]" />

<x-form.text name="order_amount" label="Order Amount (₹)" type="number" :value="old('order_amount', $defaultAmount)"
    help="What the customer pays for this delivery." :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.text name="delivery_slot" label="Delivery Slot" placeholder="e.g. Morning, 10am–1pm" />

<x-form.text name="latitude" label="Latitude" :attrs="['step' => 'any']" />

<x-form.text name="longitude" label="Longitude" :attrs="['step' => 'any']" />

<x-form.textarea name="notes" label="Notes" rows="2" />

<x-form.actions :cancel="route('admin.order.index')" label="Place Order" />
