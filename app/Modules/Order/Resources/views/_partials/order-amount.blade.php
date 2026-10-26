<script>
    // The order form's arithmetic: picking a driver shows the rate of the
    // tanker they hold, and rate × count is the total. The server recomputes
    // all of it on save — this only spares the admin from guessing.
    (function() {
        const rates = @json($drivers->mapWithKeys(fn($d) => [
            $d->id => (float) ($d->currentAssignment?->tanker?->tanker_rate ?? 0),
        ])->all());

        const driver = document.getElementById('driver_id');
        const rate = document.getElementById('tanker_rate');
        const count = document.getElementById('tanker_count');
        const total = document.getElementById('order-total');
        const note = document.getElementById('order-total-note');

        if (!driver || !rate || !count || !total) return;

        // The rate follows the driver until the admin types over it. After
        // that their figure stands, because overriding it is the whole point
        // of the field — refilling it on the next driver change would quietly
        // discard what they entered.
        let rateIsTheirs = rate.value !== '';

        function render() {
            const amount = (parseFloat(rate.value) || 0) * (parseInt(count.value, 10) || 0);

            total.textContent = '₹ ' + amount.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        driver.addEventListener('change', function() {
            const picked = rates[this.value];

            if (picked !== undefined && !rateIsTheirs) {
                rate.value = picked > 0 ? picked.toFixed(2) : '';
            }

            if (note) {
                note.textContent = picked !== undefined && picked <= 0
                    ? 'This driver\'s tanker has no rate set — enter one, or the order saves at ₹ 0.'
                    : 'Rate per tanker × number of tankers.';
            }

            render();
        });

        rate.addEventListener('input', function() {
            rateIsTheirs = true;
            render();
        });

        count.addEventListener('input', render);

        render();
    })();
</script>
