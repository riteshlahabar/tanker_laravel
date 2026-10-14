<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Order Details</h4>
        <span class="badge {{ $order->status->badge() }}">{{ $order->status->label() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordernone mb-0">
                <tbody>
                    <tr>
                        <th>Customer</th>
                        <td>{{ $order->customer?->full_name }} · {{ $order->customer?->mobile }}</td>
                    </tr>
                    <tr>
                        <th>Delivery address</th>
                        <td>{{ $order->delivery_address }}</td>
                    </tr>
                    <tr>
                        <th>Delivery date</th>
                        <td>{{ $order->delivery_date?->format('d M Y') }} {{ $order->delivery_slot }}</td>
                    </tr>
                    <tr>
                        <th>Quantity</th>
                        <td>{{ $order->quantity_litres ? number_format($order->quantity_litres) . ' L' : '—' }}</td>
                    </tr>
                    <tr>
                        <th>Order amount</th>
                        <td>₹ {{ number_format((float) $order->order_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <th>Driver</th>
                        <td>{{ $order->driver?->full_name ?? 'Not assigned' }}</td>
                    </tr>
                    <tr>
                        <th>Tanker</th>
                        <td>{{ $order->tanker?->label() ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Refill spot</th>
                        <td>{{ $order->spot?->name ?? '—' }}
                            @if ($order->refill_charge > 0)
                                <span class="f-light">(₹ {{ number_format((float) $order->refill_charge, 2) }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Driver earning</th>
                        <td>₹ {{ number_format((float) $order->driver_earning, 2) }}</td>
                    </tr>
                    @if ($order->cancel_reason)
                        <tr>
                            <th>Cancelled because</th>
                            <td class="txt-danger">{{ $order->cancel_reason }}</td>
                        </tr>
                    @endif
                    @if ($order->notes)
                        <tr>
                            <th>Notes</th>
                            <td>{{ $order->notes }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
