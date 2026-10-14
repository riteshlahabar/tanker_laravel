@php($spot = $spot ?? null)

<x-form.text name="name" label="Spot Name" :value="$spot?->name" placeholder="e.g. Hadapsar Borewell" required />

<x-form.text name="refill_charge" label="Refill Charge (₹)" type="number" :value="$spot?->refill_charge"
    placeholder="e.g. 400.00" help="One-time charge to fill a tanker here." :attrs="['step' => '0.01', 'min' => 0]"
    required />

<x-form.textarea name="address" label="Address" :value="$spot?->address" rows="2" />

<x-form.text name="latitude" label="Latitude" :value="$spot?->latitude" placeholder="e.g. 18.5204"
    :attrs="['step' => 'any']" />

<x-form.text name="longitude" label="Longitude" :value="$spot?->longitude" placeholder="e.g. 73.8567"
    help="Needed for the driver's nearest-spot list." :attrs="['step' => 'any']" />

<x-form.text name="water_type" label="Water Type" :value="$spot?->water_type" placeholder="e.g. Borewell, RO" />

<x-form.text name="contact_person" label="Contact Person" :value="$spot?->contact_person" />

<x-form.text name="contact_mobile" label="Contact Mobile" :value="$spot?->contact_mobile" placeholder="e.g. 9876543210" />

<x-form.select name="is_available" label="Availability" :value="$spot?->is_available ?? true"
    :options="[1 => 'Available', 0 => 'Unavailable']" required />

<x-form.status :value="$spot?->status ?? true" />

<x-form.actions :cancel="route('admin.spot.index')" :label="$spot ? 'Update Spot' : 'Save Spot'" />
