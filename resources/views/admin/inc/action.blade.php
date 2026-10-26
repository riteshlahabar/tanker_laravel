{{-- Row widget used by every DataTable: optional extra links, edit, delete with
     confirmation, or a status switch. Mirrors Cuba's admin action markup. --}}
<div class="action-div">
    @isset($extra)
        {!! $extra !!}
    @endisset

    @isset($data)
        @isset($edit)
            <a class="edit-icon" href="{{ route($edit, $data) }}" title="{{ __('Edit') }}">
                <i data-feather="edit"></i>
            </a>
        @endisset

        @isset($delete)
            <a class="delete-svg" href="#deleteModal{{ $data->id }}" data-bs-toggle="modal"
                title="{{ __('Delete') }}">
                <i class="remove-icon" data-feather="trash-2"></i>
            </a>
            @include('admin.inc.delete-modal', [
                'data' => $data,
                'delete' => $delete,
                'note' => $note ?? null,
            ])
        @endisset
    @endisset

    @isset($toggle)
        <label class="switch mb-0">
            <input class="form-check-input toggle-status" type="checkbox" name="{{ $name }}"
                data-route="{{ route($route, $toggle->id) }}" data-id="{{ $toggle->id }}" value="1"
                {{ $value ? 'checked' : '' }} @if ($toggle->system_reserve ?? false) disabled @endif>
            <span class="switch-state"></span>
        </label>
    @endisset
</div>
