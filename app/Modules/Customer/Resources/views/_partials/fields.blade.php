@php($customer = $customer ?? null)

<x-form.text name="first_name" label="First Name" :value="$customer?->first_name" required />

<x-form.text name="last_name" label="Last Name" :value="$customer?->last_name" />

<x-form.text name="mobile" label="Mobile Number" :value="$customer?->mobile" placeholder="e.g. 9876543210"
    help="Used to sign in to the app." required />

<x-form.status :value="$customer?->status ?? true" />

<x-form.password-pair :editing="$customer !== null" />

<x-form.textarea name="address" label="Delivery Address" :value="$customer?->address" rows="2" />

<x-form.text name="latitude" label="Latitude" :value="$customer?->latitude" :attrs="['step' => 'any']" />

<x-form.text name="longitude" label="Longitude" :value="$customer?->longitude" :attrs="['step' => 'any']" />

<x-form.actions :cancel="route('admin.customer.index')" :label="$customer ? 'Update Customer' : 'Save Customer'" />
