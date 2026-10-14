@props(['cancel', 'label' => 'Save'])

<div class="col-12">
    <hr class="mt-4">
    <a class="btn btn-light" href="{{ $cancel }}">Cancel</a>
    <button class="btn btn-primary ms-2" type="submit">{{ $label }}</button>
</div>
