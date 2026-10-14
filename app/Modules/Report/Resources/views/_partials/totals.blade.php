@if ($totals !== [])
    <div class="row">
        @foreach ($totals as $label => $value)
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 class="mb-1">{{ $value }}</h4>
                        <span class="f-light">{{ $label }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
