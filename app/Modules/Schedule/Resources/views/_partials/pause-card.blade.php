{{-- Holidays and customers who are away. Paused dates are simply missed —
     there is no catch-up pile when it resumes. --}}
<div class="card">
    <div class="card-header"><h4 class="mb-0">Pause</h4></div>
    <div class="card-body">
        @if ($schedule->is_paused)
            <p class="f-m-light">
                Paused{{ $schedule->pause_until ? ' until '.$schedule->pause_until->format('d M Y') : ' indefinitely' }}.
                No orders are being created.
            </p>
            <form action="{{ route('admin.schedule.resume', $schedule) }}" method="POST">
                @csrf
                <button class="btn btn-primary" type="submit">Resume Routine</button>
            </form>
        @else
            <form action="{{ route('admin.schedule.pause', $schedule) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-7">
                    <label class="form-label" for="pause_until">Pause Until</label>
                    <input class="form-control" id="pause_until" type="date" name="pause_until"
                        min="{{ now()->toDateString() }}">
                    <small class="form-text text-muted">Leave empty to pause until you resume it.</small>
                </div>
                <div class="col-sm-5">
                    <button class="btn btn-outline-warning w-100" type="submit">Pause Routine</button>
                </div>
            </form>
        @endif
    </div>
</div>
