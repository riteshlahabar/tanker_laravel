<script>
    // Picking a customer fills in the address they are registered with; the
    // admin can still type a different one for this delivery.
    (function() {
        const addresses = @json($customers->pluck('address', 'id'));
        const select = document.getElementById('customer_id');
        const address = document.getElementById('delivery_address');

        if (!select || !address) return;

        select.addEventListener('change', function() {
            if (address.value.trim() === '' && addresses[this.value]) {
                address.value = addresses[this.value];
            }
        });
    })();
</script>
