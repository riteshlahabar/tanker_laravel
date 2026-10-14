<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="status" label="Status"
        :options="['' => 'All', 'pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed']" />
    <x-filter-select id="template" label="Template" :options="['' => 'All'] + $templates" />
</div>
