@props(['name', 'label', 'required' => false, 'help' => null, 'width' => 'col-md-6'])

<div class="{{ $width }}">
    <label class="form-label" for="{{ $name }}">
        {{ $label }}@if ($required)<span class="txt-danger"> *</span>@endif
    </label>
    {{ $slot }}
    @if ($help)
        <small class="form-text text-muted">{{ $help }}</small>
    @endif
    @error($name)
        <span class="d-block text-danger mt-1"><strong>{{ $message }}</strong></span>
    @enderror
</div>
