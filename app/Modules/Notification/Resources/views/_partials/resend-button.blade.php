{{--
    Put one message back on the queue. Drawn per row by the DataTable, so it
    stays a form (and keeps its CSRF token) rather than a link — resending is a
    write, and a GET that sends WhatsApp messages would fire on any crawl or
    prefetch of the page.
--}}
<form action="{{ route('admin.whatsapp.resend', $message) }}" method="POST" class="d-inline">
    @csrf
    <button class="btn btn-outline-primary btn-xs text-nowrap" type="submit"
        title="Queue this message again">
        <i data-feather="refresh-cw"></i> Resend
    </button>
</form>
