@props(['value' => true, 'width' => 'col-md-6'])

<x-form.select name="status" label="Status" :value="$value ? 1 : 0" :options="[1 => 'Active', 0 => 'Inactive']"
    :width="$width" required />
