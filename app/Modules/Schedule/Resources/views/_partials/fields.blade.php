@php($schedule = $schedule ?? null)

@if ($schedule === null)
    <x-form.select name="customer_id" label="Customer" :options="$customers" :value="$schedule?->customer_id"
        placeholder="Choose a customer" help="Set their 'Can Place Own Orders' to No on the customer screen."
        required />
@endif

@include('schedule::_partials.pattern-fields', ['schedule' => $schedule])
@include('schedule::_partials.delivery-fields', ['schedule' => $schedule])

<x-form.textarea name="notes" label="Notes" :value="$schedule?->notes" rows="2"
    placeholder="Anything the driver should know" />

<x-form.status :value="$schedule?->status ?? true" />

<x-form.actions :cancel="route('admin.schedule.index')"
    :label="$schedule ? 'Update Standing Order' : 'Save Standing Order'" />
