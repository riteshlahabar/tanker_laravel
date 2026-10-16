@php($tanker = $tanker ?? null)

<x-form.text name="vehicle_name" label="Vehicle Name" :value="$tanker?->vehicle_name"
    placeholder="e.g. Water Tanker 1" required />

<x-form.text name="vehicle_number" label="Vehicle Number" :value="$tanker?->vehicle_number"
    placeholder="e.g. MH12AB1234" help="Registration number, stored in capitals." required />

<x-form.text name="tanker_rate" label="Rate per Tanker (₹)" type="number" :value="$tanker?->tanker_rate"
    placeholder="e.g. 1200"
    help="What one tanker-load from this vehicle costs the customer. Leave blank to use the Price per Tanker setting."
    :attrs="['step' => '0.01', 'min' => 0]" />

<x-form.text name="capacity_litres" label="Capacity (litres)" type="number" :value="$tanker?->capacity_litres"
    placeholder="e.g. 5000" help="Vehicle size, for your own reference. It does not affect the price."
    :attrs="['min' => 1, 'max' => 200000]" />

<x-form.text name="model_year" label="Model Year" type="number" :value="$tanker?->model_year" placeholder="e.g. 2021"
    :attrs="['min' => 1950, 'max' => date('Y') + 1]" />

<x-form.text name="rc_number" label="RC Number" :value="$tanker?->rc_number" />

<x-form.text name="insurance_expiry" label="Insurance Expiry" type="date"
    :value="$tanker?->insurance_expiry?->format('Y-m-d')" />

<x-form.status :value="$tanker?->status ?? true" />

<x-form.textarea name="notes" label="Notes" :value="$tanker?->notes" rows="3" />

<x-form.actions :cancel="route('admin.tanker.index')" :label="$tanker ? 'Update Tanker' : 'Save Tanker'" />
