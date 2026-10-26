@php($spot = $spot ?? null)

<x-form.text name="name" label="Filling Point Name" :value="$spot?->name" placeholder="e.g. Hadapsar Borewell" required />

<x-form.text name="refill_charge" label="Refill Charge (₹)" type="number" :value="$spot?->refill_charge"
    placeholder="e.g. 400.00" help="One-time charge to fill a tanker here." :attrs="['step' => '0.01', 'min' => 0]"
    required />

{{-- One column, beside the rest of the details: the address is a line or two,
     and a full-width box for it pushed every other field down a row. --}}
<x-form.textarea name="address" label="Address" :value="$spot?->address" rows="2" width="col-md-6" required />

<x-form.text name="water_type" label="Water Type" :value="$spot?->water_type" placeholder="e.g. Borewell, RO" />

<x-form.text name="contact_person" label="Contact Person" :value="$spot?->contact_person" required />

<x-form.text name="contact_mobile" label="Contact Mobile" :value="$spot?->contact_mobile"
    placeholder="e.g. 9876543210" help="Who the driver rings if the point is shut or the queue is long." required />

<x-form.select name="is_available" label="Availability" :value="$spot?->is_available ?? true"
    :options="[1 => 'Available', 0 => 'Unavailable']" required />

<x-form.status :value="$spot?->status ?? true" />

<x-form.actions :cancel="route('admin.spot.index')" :label="$spot ? 'Update Filling Point' : 'Save Filling Point'" />
