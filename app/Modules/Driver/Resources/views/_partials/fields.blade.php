@php($driver = $driver ?? null)

<x-form.text name="first_name" label="First Name" :value="$driver?->first_name" required />

<x-form.text name="last_name" label="Last Name" :value="$driver?->last_name" />

<x-form.text name="mobile" label="Mobile Number" :value="$driver?->mobile" placeholder="e.g. 9876543210"
    help="Used to sign in to the app." required />

<x-form.select name="tanker_id" label="Assign Tanker" :value="$currentTankerId ?? null" :options="$tankerOptions"
    placeholder="— No tanker —" help="Only tankers not already assigned to another driver are listed." />

<x-form.text name="monthly_salary" label="Monthly Salary (₹)" type="number"
    :value="$driver?->monthly_salary ?? ($defaultSalary ?? 15000)"
    help="What this driver is paid each month. Not divided across deliveries."
    :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.status :value="$driver?->status ?? true" />

<x-form.password-pair :editing="$driver !== null" />

<x-form.textarea name="address" label="Address" :value="$driver?->address" rows="2" />

<x-form.actions :cancel="route('admin.driver.index')" :label="$driver ? 'Update Driver' : 'Save Driver'" />
