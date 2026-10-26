@php($customer = $customer ?? null)

<x-form.text name="first_name" label="First Name" :value="$customer?->first_name" required />

<x-form.text name="last_name" label="Last Name" :value="$customer?->last_name" />

<x-form.text name="mobile" label="Mobile Number" :value="$customer?->mobile" placeholder="e.g. 9876543210"
    help="Exactly 10 digits. Used to sign in to the app." required
    :attrs="['maxlength' => 10, 'inputmode' => 'numeric']" />

<x-form.status :value="$customer?->status ?? true" />

{{-- Turned off for a standing-order customer: the office manages their
     deliveries, so the app refuses an order they place themselves. --}}
<x-form.select name="can_self_order" label="Can Place Own Orders"
    :value="($customer?->can_self_order ?? true) ? 1 : 0"
    :options="[1 => 'Yes — orders from the app', 0 => 'No — fixed routine managed by the office']"
    help="Set to No for customers on a standing order." required />

{{-- Password, confirmation and the delivery address share one row: a third
     each. The address was a full-width box of its own underneath, which left
     the form with a wide empty strip and pushed the Save button off the first
     screen. --}}
<x-form.password-pair :editing="$customer !== null" width="col-md-4" />

<x-form.textarea name="address" label="Delivery Address" :value="$customer?->address" rows="2"
    width="col-md-4" required />

<x-form.actions :cancel="route('admin.customer.index')" :label="$customer ? 'Update Customer' : 'Save Customer'" />
