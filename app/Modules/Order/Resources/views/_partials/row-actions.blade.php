<div class="action-div">
    <a class="edit-icon" href="{{ route('admin.order.show', $order) }}" title="{{ __('View') }}">
        <i data-feather="eye"></i>
    </a>
    @if ($order->status === \App\Modules\Core\Enums\OrderStatusEnum::Pending)
        <a class="edit-icon" href="{{ route('admin.order.assign.form', $order) }}" title="{{ __('Assign') }}">
            <i data-feather="user-check"></i>
        </a>
    @endif
</div>
