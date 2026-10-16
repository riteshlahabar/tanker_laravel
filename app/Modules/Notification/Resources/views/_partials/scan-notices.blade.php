@if (! $configured)
    <div class="alert alert-danger inverse" role="alert">
        <i class="icon-alert"></i>
        <p class="mb-0">
            The scan service is not configured. Set <code>WHATSAPP_SCAN_BASE_URL</code> and
            <code>WHATSAPP_SCAN_API_KEY</code> in <code>.env</code>.
        </p>
    </div>
@elseif ($driver !== 'scan')
    <div class="alert alert-warning inverse" role="alert">
        <i class="icon-alert"></i>
        <p class="mb-0">
            You can pair WhatsApp here, but messages are still going to the
            <strong>{{ $driver }}</strong> driver. Set <code>WHATSAPP_DRIVER=scan</code> in
            <code>.env</code> to send them through this service.
        </p>
    </div>
@endif

@if ($status->needsLogout())
    <div class="alert alert-warning inverse" role="alert">
        <i class="icon-alert"></i>
        <p class="mb-0">
            WhatsApp has rejected the saved login (code 401) and the service keeps retrying it, so
            no new QR is offered. Press <strong>Log out</strong> to clear the session and get a
            fresh code.
        </p>
    </div>
@endif
