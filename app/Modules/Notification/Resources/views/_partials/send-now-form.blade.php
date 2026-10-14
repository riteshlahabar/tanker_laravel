<form class="d-flex gap-2 align-items-center" action="{{ route('admin.whatsapp.daily-report') }}" method="POST">
    @csrf
    <input class="form-control" type="date" name="date" value="{{ now()->toDateString() }}"
        max="{{ now()->toDateString() }}">
    <button class="btn btn-primary text-nowrap" type="submit">
        <i data-feather="send"></i> Send Daily Report
    </button>
</form>
