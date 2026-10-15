<tr class="{{ $date->skipped ? 'text-muted' : '' }}">
    <td class="{{ $date->skipped ? 'text-decoration-line-through' : '' }}">
        {{ $date->date->format('D, d M Y') }}
    </td>
    <td>{{ $date->dueAt->format('g:i A') }}</td>
    <td>
        @if ($date->order === null)
            <span class="badge badge-light-secondary">Not yet created</span>
        @else
            <a href="{{ route('admin.order.show', $date->order) }}">{{ $date->order->order_no }}</a>
            <span class="badge {{ $date->order->status->badge() }} ms-1">{{ $date->order->status->label() }}</span>
        @endif
    </td>
    <td class="text-end">
        @if ($date->skipped)
            <form class="d-inline"
                action="{{ route('admin.schedule.skips.destroy', [$schedule, $date->date->toDateString()]) }}"
                method="POST">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-primary" type="submit">Restore</button>
            </form>
        @else
            @include('schedule::_partials.skip-button', ['date' => $date])
        @endif
    </td>
</tr>
