{{-- Shared AJAX handler for every .toggle-status switch rendered by a DataTable. --}}
<script>
    $(document).ready(function() {
        $(document).on('change', '.toggle-status', function() {
            let toggle = $(this);
            $.ajax({
                type: 'PUT',
                url: toggle.data('route'),
                data: {
                    status: toggle.prop('checked') ? 1 : 0,
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    toastr.success('Status updated successfully');
                },
                error: function() {
                    toggle.prop('checked', !toggle.prop('checked'));
                    toastr.error('Could not update the status');
                }
            });
        });
    });
</script>
