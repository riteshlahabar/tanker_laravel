@props(['heading', 'create' => null, 'export' => null, 'createLabel' => 'Add New'])

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h4 class="mb-0">{{ $heading }}</h4>
    <div class="d-flex gap-2">
        @if ($export)
            <a class="btn btn-outline-primary" id="export-link" href="{{ $export }}">
                <i data-feather="download"></i> Export Excel
            </a>
        @endif
        @if ($create)
            <a class="btn btn-primary" href="{{ $create }}">
                <i data-feather="plus"></i> {{ $createLabel }}
            </a>
        @endif
    </div>
</div>
