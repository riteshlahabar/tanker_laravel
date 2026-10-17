<div class="row mb-3 g-2 align-items-end">
    <x-filter-select id="driver_id" label="Driver" :options="['' => 'All drivers'] + $driverFilterOptions" />
    <x-filter-select id="category" label="Category"
        :options="['' => 'All categories'] + \App\Modules\Core\Enums\ExpenseCategoryEnum::options()" />
    <div class="col-md-2">
        <label class="form-label" for="filter-from">From</label>
        <input type="date" id="filter-from" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="filter-to">To</label>
        <input type="date" id="filter-to" class="form-control">
    </div>
</div>
