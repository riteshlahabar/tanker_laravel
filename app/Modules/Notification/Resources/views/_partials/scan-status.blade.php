<div class="card">
    <div class="card-header card-no-border">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Connection</h4>
            <span class="badge badge-light-{{ $status->badge() }}" id="scan-state-badge">
                {{ $status->label() }}
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th class="f-light" style="width: 38%">Service</th>
                        <td><code>{{ $baseUrl ?: 'not set' }}</code></td>
                    </tr>
                    <tr>
                        <th class="f-light">Paired number</th>
                        <td id="scan-number">{{ $status->number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="f-light">Message driver</th>
                        <td>
                            <span class="badge badge-light-{{ $driver === 'scan' ? 'success' : 'warning' }}">
                                {{ $driver }}
                            </span>
                        </td>
                    </tr>
                    @if ($status->lastError)
                        <tr>
                            <th class="f-light">Last error</th>
                            <td class="f-12" id="scan-last-error">{{ $status->lastError }}</td>
                        </tr>
                    @endif
                    @if ($status->disconnectReason)
                        <tr>
                            <th class="f-light">Last disconnect</th>
                            <td class="f-12">
                                {{ $status->disconnectReason }}
                                @if ($status->disconnectCode)
                                    (code {{ $status->disconnectCode }})
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
