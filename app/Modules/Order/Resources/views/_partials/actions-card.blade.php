@php($next = $order->status->allowedTransitions())

@if ($next !== [])
    <div class="card">
        <div class="card-header">
            <h4>Move this order on</h4>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
            {{-- Not one of the status transitions: the order stays assigned, it
                 just changes hands. Offered only while it is still waiting,
                 since a refill has already been paid for by its driver. --}}
            @if ($order->status === \App\Modules\Core\Enums\OrderStatusEnum::Assigned)
                <a class="btn btn-outline-primary" href="{{ route('admin.order.reassign.form', $order) }}">
                    Change Driver
                </a>
            @endif

            @foreach ($next as $status)
                @if ($status === \App\Modules\Core\Enums\OrderStatusEnum::Assigned)
                    <a class="btn btn-primary" href="{{ route('admin.order.assign.form', $order) }}">
                        Assign Driver
                    </a>
                @elseif ($status === \App\Modules\Core\Enums\OrderStatusEnum::Cancelled)
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal"
                        data-bs-target="#cancelOrderModal">Cancel Order</button>
                @else
                    <form action="{{ route('admin.order.status', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $status->value }}">
                        <button class="btn btn-primary" type="submit">{{ $status->label() }}</button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>

    @include('order::_partials.cancel-modal')
@endif
