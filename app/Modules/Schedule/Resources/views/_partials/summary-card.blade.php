<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">The Routine</h4>
        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.schedule.edit', $schedule) }}">Edit</a>
    </div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th class="ps-0">Pattern</th><td>{{ $schedule->patternLabel() }}</td></tr>
                <tr><th class="ps-0">Window</th><td>{{ $schedule->slot?->label() ?? $schedule->slotLabel() ?? '—' }}</td></tr>
                <tr><th class="ps-0">Litres</th><td>{{ $schedule->quantity_litres ?? '—' }}</td></tr>
                <tr>
                    <th class="ps-0">Amount</th>
                    <td>{{ $schedule->order_amount === null ? 'Usual pricing' : '₹ '.number_format((float) $schedule->order_amount, 2) }}</td>
                </tr>
                <tr>
                    <th class="ps-0">Usual driver</th>
                    <td>{{ $schedule->preferredDriver?->full_name ?? 'Assigned by admin' }}</td>
                </tr>
                <tr><th class="ps-0">Runs</th><td>
                    {{ $schedule->start_date?->format('d M Y') }} –
                    {{ $schedule->end_date?->format('d M Y') ?? 'no end date' }}
                </td></tr>
                <tr><th class="ps-0">Next delivery</th><td>
                    @if ($schedule->is_paused)
                        <span class="badge badge-light-warning">Paused</span>
                    @else
                        {{ $schedule->next_run_date?->format('D, d M Y') ?? 'Finished' }}
                    @endif
                </td></tr>
                <tr><th class="ps-0">Address</th>
                    <td>{{ $schedule->delivery_address ?: $schedule->customer?->address ?? '—' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
