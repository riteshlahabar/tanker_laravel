<div class="card">
    <div class="card-header">
        <h4>Assignment History</h4>
    </div>
    <div class="card-body">
        @if ($history->isEmpty())
            <p class="mb-0 f-light">No tanker has been assigned to this driver yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordernone">
                    <thead>
                        <tr>
                            <th>Tanker</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $row)
                            <tr>
                                <td>{{ $row->tanker?->label() ?? '—' }}</td>
                                <td>{{ $row->assigned_from->format('d M Y') }}</td>
                                <td>
                                    @if ($row->assigned_to)
                                        {{ $row->assigned_to->format('d M Y') }}
                                    @else
                                        <span class="badge badge-light-success">Current</span>
                                    @endif
                                </td>
                                <td>{{ $row->reason ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
