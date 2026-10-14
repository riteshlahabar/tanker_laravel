@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'width' => 'col-md-6',
])

<x-form.field :name="$name" :label="$label" :required="$required" :help="$help" :width="$width">
    <select class="form-select @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}"
        @if ($required) required @endif>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
</x-form.field>
