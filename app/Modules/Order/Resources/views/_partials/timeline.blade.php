<div class="card">
    <div class="card-header">
        <h4>Timeline</h4>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            @foreach ($order->statusLogs as $log)
                <li class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <span class="badge {{ $log->status->badge() }}">{{ $log->status->label() }}</span>
                            @if ($log->note)
                                <p class="mb-0 mt-1 f-light">{{ $log->note }}</p>
                            @endif
                            <small class="f-light">by {{ $log->changedBy?->full_name ?? 'system' }}</small>
                        </div>
                        <small class="f-light text-nowrap">{{ $log->created_at->format('d M, H:i') }}</small>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
