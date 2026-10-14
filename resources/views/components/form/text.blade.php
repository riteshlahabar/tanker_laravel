@props([
    'name',
    'label',
    'value' => null,
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'help' => null,
    'width' => 'col-md-6',
    'attrs' => [],
])

<x-form.field :name="$name" :label="$label" :required="$required" :help="$help" :width="$width">
    <input class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" type="{{ $type }}"
        name="{{ $name }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}"
        @if ($required) required @endif @foreach ($attrs as $k => $v) {{ $k }}="{{ $v }}" @endforeach>
</x-form.field>
