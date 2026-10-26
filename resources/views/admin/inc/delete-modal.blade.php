<div class="modal fade" id="deleteModal{{ $data->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm delete') }}</h5>
                <button class="btn-close py-0" type="button" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-2">{{ __('Are you sure you want to delete this record?') }}</h6>
                <p class="mb-0 f-light">{{ $note ?? __('This cannot be undone.') }}</p>
            </div>
            <div class="modal-footer">
                <form action="{{ route($delete, $data->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button class="btn btn-danger ms-2" type="submit">{{ __('Delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
