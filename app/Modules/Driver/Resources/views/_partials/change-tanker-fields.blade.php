<x-form.select name="tanker_id" label="New Tanker" :value="$currentTankerId" :options="$tankerOptions"
    placeholder="— Release the current tanker —" width="col-12"
    help="Reassigning closes the current record and opens a new one, so the history is kept." />

<x-form.text name="reason" label="Reason" width="col-12"
    placeholder="e.g. Driver absent, tanker under service" />

<x-form.actions :cancel="route('admin.driver.index')" label="Update Assignment" />
