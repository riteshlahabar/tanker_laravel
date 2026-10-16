{{--
    Mirrors NotificationServiceProvider's binding: anything that is not `node`
    or `scan` falls through to the LogGateway. Testing only for `node` dated
    this notice the moment the `scan` driver was added - it then claimed a
    scan-driver panel was on `log`.
--}}
@if (! in_array($driver, ['node', 'scan'], true))
    <div class="alert alert-warning inverse" role="alert">
        <i class="icon-alert"></i>
        <p class="mb-0">
            WhatsApp is on the <strong>{{ $driver }}</strong> driver — messages are recorded here but
            not actually delivered. Set <code>WHATSAPP_DRIVER=scan</code> in <code>.env</code> to send
            them through the QR-scan service on the
            <a href="{{ route('admin.whatsapp.scan') }}">WhatsApp Scan</a> screen; nothing else needs
            changing.
        </p>
    </div>
@endif
