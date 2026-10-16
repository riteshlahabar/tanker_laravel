<div class="card">
    <div class="card-header card-no-border">
        <h4 class="mb-0">Pair WhatsApp</h4>
        <span class="f-light f-12">WhatsApp → Linked devices → Link a device</span>
    </div>
    <div class="card-body text-center" id="scan-qr-body">
        {{-- Replaced wholesale by the poller: a code lasts about 20 seconds. --}}
        @if ($status->connected)
            <div class="py-4" id="scan-qr-slot">
                <i class="font-success" data-feather="check-circle" style="width:48px;height:48px"></i>
                <h5 class="mt-3 mb-1">WhatsApp is connected</h5>
                <p class="f-light mb-0">
                    {{ $status->number ? 'Paired as '.$status->number : 'No QR needed.' }}
                </p>
            </div>
        @elseif ($qr?->hasImage())
            <div id="scan-qr-slot">
                <img class="img-fluid" src="{{ $qr->imageDataUrl }}" alt="WhatsApp QR code"
                    style="max-width: 280px">
                <p class="f-light f-12 mt-3 mb-0">The code refreshes on its own — just scan it.</p>
            </div>
        @else
            <div class="py-4" id="scan-qr-slot">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="f-light mt-3 mb-0">{{ $qr?->message ?? 'Waiting for a code…' }}</p>
            </div>
        @endif
    </div>
    <div class="card-footer">
        <form class="d-flex gap-2 flex-wrap" action="{{ route('admin.whatsapp.scan.action') }}" method="POST"
            onsubmit="return confirm('This asks the service to re-pair. Continue?')">
            @csrf
            <button class="btn btn-light" type="submit" name="action" value="restart">
                <i data-feather="refresh-cw"></i> Restart service
            </button>
            <button class="btn btn-outline-danger" type="submit" name="action" value="logout">
                <i data-feather="log-out"></i> Log out
            </button>
        </form>
    </div>
</div>
