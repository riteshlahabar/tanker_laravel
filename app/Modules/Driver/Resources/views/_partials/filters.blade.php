<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="status" label="Status" :options="['' => 'All', 1 => 'Active', 0 => 'Inactive']" />
    <x-filter-select id="assigned" label="Tanker"
        :options="['' => 'All', 1 => 'With tanker', 0 => 'Without tanker']" />
</div>
