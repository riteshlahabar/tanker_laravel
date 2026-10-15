@php($slot = $slot ?? null)

<x-form.text name="name" label="Slot Name" :value="$slot?->name" placeholder="e.g. Morning" required />

<x-form.text name="sort_order" label="Display Order" type="number" :value="$slot?->sort_order ?? 0"
    help="Lowest first in dropdowns." :attrs="['min' => 0, 'max' => 999]" />

<x-form.text name="start_time" label="Starts At" type="time"
    :value="$slot ? \Illuminate\Support\Carbon::parse($slot->start_time)->format('H:i') : '08:00'"
    help="The six-hour skip notice counts back from this time." required />

<x-form.text name="end_time" label="Ends At" type="time"
    :value="$slot ? \Illuminate\Support\Carbon::parse($slot->end_time)->format('H:i') : '12:00'" required />

<x-form.status :value="$slot?->status ?? true" />

<x-form.actions :cancel="route('admin.slot.index')" :label="$slot ? 'Update Slot' : 'Save Slot'" />
