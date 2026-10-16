<div class="card">
    <div class="card-header card-no-border">
        <h4 class="mb-0">Send a test message</h4>
        <span class="f-light f-12">
            Goes straight out, not through the queue — so the answer here is the service's own.
        </span>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.whatsapp.scan.test') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="scan-test-phone">Mobile number</label>
                    <input class="form-control @error('phone') is-invalid @enderror" id="scan-test-phone"
                        type="text" name="phone" inputmode="numeric"
                        value="{{ old('phone', config('services.whatsapp.admin_number')) }}"
                        placeholder="9812345678">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="f-light">Digits only. 91 is added for a ten-digit number.</small>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="scan-test-message">Message</label>
                    <input class="form-control @error('message') is-invalid @enderror" id="scan-test-message"
                        type="text" name="message" value="{{ old('message') }}"
                        placeholder="Leave blank for the default test message">
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <button class="btn btn-primary mt-3" type="submit" @disabled(! $status->connected)>
                <i data-feather="send"></i> Send test
            </button>
            @unless ($status->connected)
                <small class="d-block f-light mt-2">Pair WhatsApp first — the service refuses a send while it is not connected.</small>
            @endunless
        </form>
    </div>
</div>
