<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="status" label="Status" :options="['' => 'All', 1 => 'Active', 0 => 'Inactive']" />
    <x-filter-select id="is_available" label="Availability"
        :options="['' => 'All', 1 => 'Available', 0 => 'Unavailable']" />
</div>
