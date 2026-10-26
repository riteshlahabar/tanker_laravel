<div class="card">
    <div class="card-header">
        <h4>Daily Report</h4>
    </div>
    <div class="card-body">
        <p class="f-light">
            Every night at <strong>23:59</strong> the owner receives each driver's delivered
            tanker count and earning on WhatsApp.
        </p>
        <p class="f-light mb-3">
            It needs the scheduler running:<br>
            <code>php artisan schedule:run</code> every minute via cron or Task Scheduler.
        </p>
        <a class="btn btn-outline-primary btn-icon" href="{{ route('admin.whatsapp.index') }}">
            <i data-feather="message-square"></i> View WhatsApp Log
        </a>
    </div>
</div>
