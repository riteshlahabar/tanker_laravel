{{--
    Keeps the screen current while it is open. A QR lasts roughly twenty
    seconds before the service replaces it, so a static render would show a
    code that has already expired; this asks every four seconds and swaps the
    panel. Once the phone is paired it stops asking and reloads once, so the
    rest of the page (the test form's button, the status rows) comes back in
    its connected state rather than being patched field by field.
--}}
<script>
    (function () {
        const endpoint = @json(route('admin.whatsapp.scan.state'));
        const slotHost = document.getElementById('scan-qr-body');
        const badge = document.getElementById('scan-state-badge');
        const everyMs = 4000;
        let stopped = false;

        function paint(data) {
            if (badge) {
                badge.textContent = data.label;
                badge.className = 'badge badge-light-' + data.badge;
            }

            const slot = document.getElementById('scan-qr-slot');

            if (!slot) return;

            if (data.qr_image) {
                slot.innerHTML =
                    '<img class="img-fluid" src="' + data.qr_image + '" alt="WhatsApp QR code" style="max-width: 280px">' +
                    '<p class="f-light f-12 mt-3 mb-0">The code refreshes on its own — just scan it.</p>';

                return;
            }

            const note = data.qr_message || data.last_error || 'Waiting for a code…';

            slot.innerHTML =
                '<div class="py-4"><div class="spinner-border text-primary" role="status"></div>' +
                '<p class="f-light mt-3 mb-0"></p></div>';
            slot.querySelector('p').textContent = note;
        }

        async function tick() {
            if (stopped) return;

            try {
                const response = await fetch(endpoint, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) return;

                const data = await response.json();

                // Paired: stop polling and let the page redraw itself whole.
                if (data.connected) {
                    stopped = true;
                    window.location.reload();

                    return;
                }

                paint(data);
            } catch (error) {
                // A missed poll is not worth reporting; the next one will do.
            }
        }

        if (slotHost && !@json($status->connected)) {
            setInterval(tick, everyMs);
            tick();
        }
    })();
</script>
