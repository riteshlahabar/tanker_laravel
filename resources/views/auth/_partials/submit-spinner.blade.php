<script>
    $('.btn.spinner-btn').click(function(event) {
        event.preventDefault();

        var $btn = $(this);
        $btn.removeClass('btn-block').prop('disabled', true);
        $btn.append('<span class="spinner-border spinner-border-sm spinner_loader" role="status" aria-hidden="true"></span>');

        $(this).parents('form')[0].submit();
    });
</script>
