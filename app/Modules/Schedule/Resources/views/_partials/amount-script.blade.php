<script>
    // The standing order's arithmetic, deliberately identical to the New Order
    // form's: choosing the usual driver shows the rate of the tanker they hold,
    // and rate × tankers per delivery is what each visit comes to. The server
    // recomputes it on every generated order — this only spares the admin from
    // guessing what the routine will bill.
    (function() {
        const rates = @json($driverRates ?? []);

        const driver = document.getElementById('preferred_driver_id');
        const rate = document.getElementById('tanker_rate');
        const count = document.getElementById('tanker_count');
        const total = document.getElementById('schedule-total');
        const note = document.getElementById('schedule-total-note');

        if (!driver || !rate || !count || !total) return;

        // An empty rate is not an override, it is "price each delivery from
        // whichever tanker goes out" — so the figure only follows the driver
        // until the admin types one of their own.
        let rateIsTheirs = rate.value !== '';

        function render() {
            const amount = (parseFloat(rate.value) || 0) * (parseInt(count.value, 10) || 0);

            total.textContent = '₹ ' + amount.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            if (note) {
                note.textContent = rate.value === ''
                    ? 'No rate set — each delivery is priced from the tanker that goes out.'
                    : 'Rate per tanker × tankers per delivery.';
            }
        }

        driver.addEventListener('change', function() {
            const picked = rates[this.value];

            if (picked !== undefined && picked > 0 && !rateIsTheirs) {
                rate.value = picked.toFixed(2);
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
