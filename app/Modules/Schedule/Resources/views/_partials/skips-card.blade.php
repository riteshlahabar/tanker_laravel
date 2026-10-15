{{-- Who called off what, and why. --}}
<div class="card">
    <div class="card-header"><h4 class="mb-0">Called-Off Dates</h4></div>
    <div class="card-body">
        @if ($skips->isEmpty())
            <p class="f-m-light mb-0">Nothing has been called off on this routine.</p>
        @else
            <div class="table-responsive custom-scrollbar">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>Date</th><th>Reason</th><th>Asked by</th><th>Cancelled order</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($skips as $skip)
                            <tr>
                                <td>{{ $skip->skip_date?->format('D, d M Y') }}</td>
                                <td>{{ $skip->reason ?: '—' }}</td>
                                <td>{{ $skip->requester?->full_name ?? '—' }}</td>
                                <td>{{ $skip->order?->order_no ?? 'none yet' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
