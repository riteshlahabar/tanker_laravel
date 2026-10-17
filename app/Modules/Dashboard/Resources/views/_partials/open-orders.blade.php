{{-- The owner's live queue: every order still to be delivered, newest first.
     A delivered or cancelled order leaves this by itself — the scope is
     OrderStatusEnum::openValues() — while the Orders screen keeps them all.
     Markup lifted from schedule::_partials.orders-card. --}}
<div class="card">
    <div class="card-header card-no-border">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-0">Orders To Deliver</h4>
                <span class="f-light">
                    @if ($openTotal === 0)
                        Nothing outstanding
                    @elseif ($openTotal > $openOrders->count())
                        Showing the latest {{ $openOrders->count() }} of {{ $openTotal }} outstanding
                    @else
                        {{ $openTotal }} outstanding
                    @endif
                </span>
            </div>
            <a href="{{ route('admin.order.index') }}" class="btn btn-outline-primary btn-sm">All Orders</a>
        </div>
    </div>
    <div class="card-body pt-0">
        @if ($openOrders->isEmpty())
            <p class="f-m-light mb-0">Every order has been delivered or cancelled. New orders appear here.</p>
        @else
            <div class="table-responsive custom-scrollbar">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Delivery</th>
                            <th>Driver</th>
                            <th>Tanker</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.order.show', $order) }}">{{ $order->order_no }}</a>
                                </td>
                                <td>{{ $order->customer?->full_name ?? '—' }}</td>
                                <td>{{ $order->delivery_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    @if ($order->driver === null)
                                        {{-- Unassigned is the one row that needs the owner, so it
                                             is said in the warning colour rather than left blank. --}}
                                        <span class="badge badge-light-warning">Unassigned</span>
                                    @else
                                        {{ $order->driver->full_name }}
                                    @endif
                                </td>
                                <td>{{ $order->tanker?->vehicle_number ?? '—' }}</td>
                                <td class="text-end">₹ {{ number_format((float) $order->order_amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $order->status->badge() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
