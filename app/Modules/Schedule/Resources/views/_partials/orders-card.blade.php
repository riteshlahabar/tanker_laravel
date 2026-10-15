{{-- What this routine has actually produced. --}}
<div class="card">
    <div class="card-header"><h4 class="mb-0">Orders Produced</h4></div>
    <div class="card-body">
        @if ($orders->isEmpty())
            <p class="f-m-light mb-0">No orders yet — the first is created the night before.</p>
        @else
            <div class="table-responsive custom-scrollbar">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>Order</th><th>Date</th><th>Driver</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td><a href="{{ route('admin.order.show', $order) }}">{{ $order->order_no }}</a></td>
                                <td>{{ $order->delivery_date?->format('d M Y') }}</td>
                                <td>{{ $order->driver?->full_name ?? 'Unassigned' }}</td>
                                <td><span class="badge {{ $order->status->badge() }}">{{ $order->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
