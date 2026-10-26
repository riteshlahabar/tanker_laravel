@php($fields = $report->filterFields())

<form class="row g-2 align-items-end mb-4" method="GET">
    <div class="col-md-2">
        <label class="form-label" for="from">From</label>
        <input class="form-control" id="from" type="date" name="from" value="{{ $filters->from }}">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="to">To</label>
        <input class="form-control" id="to" type="date" name="to" value="{{ $filters->to }}">
    </div>

    @foreach (['driver' => 'Driver', 'customer' => 'Customer', 'spot' => 'Filling Point', 'tanker' => 'Tanker'] as $key => $label)
        @if (in_array($key, $fields, true))
            @include('report::_partials.filter-option', ['key' => $key, 'label' => $label])
        @endif
    @endforeach

    @if (in_array('search', $fields, true))
        <div class="col-md-2">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" type="search" name="search"
                value="{{ $filters->search }}" placeholder="Name or number">
        </div>
    @endif

    <div class="col-md-auto">
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn btn-light ms-1" href="{{ url()->current() }}">Reset</a>
    </div>
</form>
