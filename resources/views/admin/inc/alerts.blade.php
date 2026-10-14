@foreach (['success', 'error', 'info', 'warning'] as $type)
    @if (Session::has($type))
        <div class="toastr-message" data-type="{{ $type }}">{{ session($type) }}</div>
    @endif
@endforeach

<script>
    $(document).ready(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            timeOut: 4000
        };

        $('.toastr-message').each(function() {
            let type = $(this).data('type');
            toastr[type in toastr ? type : 'info']($(this).text());
        });
    });
</script>
