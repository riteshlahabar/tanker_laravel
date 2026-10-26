<script>
    // Choosing a customer fills in the address they are registered at, exactly
    // as the New Order form does.
    //
    // Only when the box is empty, so it never overwrites an address the admin
    // has typed — and they may still clear it afterwards, which is a real
    // choice on *this* form: a routine with no address of its own resolves the
    // customer's registered address **each time an order is generated**
    // (`CustomerAddressResolver`), so it follows them if they move. A filled-in
    // address is this routine's own from then on.
    (function() {
        const addresses = @json($customerAddresses ?? []);

        const select = document.getElementById('customer_id');
        const address = document.getElementById('delivery_address');

        // The edit form does not render a customer field — the customer is
        // fixed once a routine exists — so there is nothing to wire up there.
        if (!select || !address) return;

        select.addEventListener('change', function() {
            if (address.value.trim() === '' && addresses[this.value]) {
                address.value = addresses[this.value];
            }
        });
    })();
</script>
