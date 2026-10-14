@php
    $selected = match ($key) {
        'driver' => $filters->driverId,
        'customer' => $filters->customerId,
        'spot' => $filters->spotId,
        'tanker' => $filters->tankerId,
        default => null,
    };
@endphp

<div class="col-md-2">
    <label class="form-label" for="{{ $key }}_id">{{ $label }}</label>
    <select class="form-select" id="{{ $key }}_id" name="{{ $key }}_id">
        <option value="">All</option>
        @foreach ($options[$key] ?? [] as $id => $name)
            <option value="{{ $id }}" @selected((int) $selected === (int) $id)>{{ $name }}</option>
        @endforeach
    </select>
</div>
