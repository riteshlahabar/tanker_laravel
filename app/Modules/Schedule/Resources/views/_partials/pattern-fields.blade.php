{{-- How often. Named weekdays win over the interval when both are given. --}}
<div class="col-12">
    <h6 class="mt-2 mb-0">How often</h6>
    <hr class="mt-2">
</div>

<x-form.select name="interval_days" label="Repeat Every" :value="$schedule?->interval_days ?? 1"
    :options="[1 => 'Day (daily)', 2 => 'Second day (alternate days)', 3 => 'Third day', 7 => 'Week']"
    help="Ignored when you pick named days below." required />

<div class="col-md-6">
    <label class="form-label" for="weekdays">Or Only On These Days</label>
    <select class="form-select" id="weekdays" name="weekdays[]" multiple size="4">
        @foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $day => $label)
            <option value="{{ $day }}" @selected(in_array($day, old('weekdays', $schedule?->weekdays ?? []), false))>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <small class="form-text text-muted">Leave empty to use the interval above.</small>
    @error('weekdays')
        <span class="d-block text-danger mt-1"><strong>{{ $message }}</strong></span>
    @enderror
</div>

<x-form.text name="start_date" label="Starts On" type="date"
    :value="$schedule?->start_date?->toDateString() ?? now()->toDateString()" required />

<x-form.text name="end_date" label="Ends On" type="date" :value="$schedule?->end_date?->toDateString()"
    help="Leave empty to run indefinitely." />
