@if ($driver !== 'node')
    <div class="alert alert-warning inverse" role="alert">
        <i class="icon-alert"></i>
        <p class="mb-0">
            WhatsApp is on the <strong>log</strong> driver — messages are recorded here but not
            actually delivered. Set <code>WHATSAPP_DRIVER=node</code> and <code>WHATSAPP_URL</code>
            in <code>.env</code> once the sending service is ready; nothing else needs changing.
        </p>
    </div>
@endif
