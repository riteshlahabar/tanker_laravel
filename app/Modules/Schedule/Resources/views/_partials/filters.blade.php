<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="status" label="Status" :options="['' => 'All', 1 => 'Active', 0 => 'Inactive']" />
    <x-filter-select id="paused" label="Running" :options="['' => 'All', 0 => 'Running', 1 => 'Paused']" />
    <x-filter-select id="customer_id" label="Customer" :options="$customerOptions" />
</div>
