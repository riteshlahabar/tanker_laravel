<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="status" label="Status" :options="['' => 'All statuses'] + $statuses" />
    <x-filter-select id="driver_id" label="Driver" :options="['' => 'All drivers'] + $driverOptions" />

    <div class="col-md-3">
        <label class="form-label" for="filter-from">Delivery from</label>
        <input class="form-control" id="filter-from" type="date">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="filter-to">Delivery to</label>
        <input class="form-control" id="filter-to" type="date">
    </div>
</div>
