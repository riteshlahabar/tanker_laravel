{{-- The admin overrides the six hours' notice, so can_skip here only fails on
     the status backstop — once the tanker is filling, the water is bought. --}}
@if ($date->canSkip)
    <form class="d-inline" action="{{ route('admin.schedule.skips.store', $schedule) }}" method="POST">
        @csrf
        <input type="hidden" name="date" value="{{ $date->date->toDateString() }}">
        <input class="form-control form-control-sm d-inline-block w-auto me-1" type="text" name="reason"
            placeholder="Reason (optional)" maxlength="255">
        <button class="btn btn-sm btn-outline-warning" type="submit">Skip</button>
    </form>
@else
    <span class="badge badge-light-secondary" title="{{ $date->skipReason }}">Cannot skip</span>
@endif
