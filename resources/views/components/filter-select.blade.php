@props(['id', 'label', 'options', 'width' => 'col-md-3'])

<div class="{{ $width }}">
    <label class="form-label" for="filter-{{ $id }}">{{ $label }}</label>
    <select class="form-select" id="filter-{{ $id }}">
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
</div>
