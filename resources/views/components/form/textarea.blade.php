@props(['name', 'label', 'value' => null, 'placeholder' => '', 'rows' => 3, 'required' => false, 'width' => 'col-12'])

<x-form.field :name="$name" :label="$label" :required="$required" :width="$width">
    <textarea class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}"
        rows="{{ $rows }}" placeholder="{{ $placeholder }}">{{ old($name, $value) }}</textarea>
</x-form.field>
