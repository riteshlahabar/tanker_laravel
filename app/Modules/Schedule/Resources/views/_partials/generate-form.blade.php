{{-- Runs the same job the scheduler runs at 00:05, for an owner who does not
     want to wait for tonight. --}}
<div class="alert alert-light-primary d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <strong>Nightly generation.</strong>
        Tomorrow's orders are created automatically at 00:05 so drivers can be assigned in the morning.
    </div>
    <form action="{{ route('admin.schedule.generate') }}" method="POST" class="d-flex gap-2">
        @csrf
        <input class="form-control form-control-sm" type="date" name="date"
            value="{{ now()->addDay()->toDateString() }}">
        <button class="btn btn-sm btn-primary text-nowrap" type="submit">Generate Now</button>
    </form>
</div>
