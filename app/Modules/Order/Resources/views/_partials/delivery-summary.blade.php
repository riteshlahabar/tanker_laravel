<div class="card">
    <div class="card-header">
        <h4>Delivery</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordernone mb-0">
            <tbody>
                <tr>
                    <th>Customer</th>
                    <td>{{ $order->customer?->full_name }}<br><span
                            class="f-light">{{ $order->customer?->mobile }}</span></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td>{{ $order->delivery_address }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $order->delivery_date?->format('d M Y') }} {{ $order->delivery_slot }}</td>
                </tr>
                <tr>
                    <th>Quantity</th>
                    <td>{{ $order->quantity_litres ? number_format($order->quantity_litres) . ' L' : '—' }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>₹ {{ number_format((float) $order->order_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
