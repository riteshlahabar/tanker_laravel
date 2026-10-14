<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.order.cancel', $order) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel {{ $order->order_no }}</h5>
                    <button class="btn-close py-0" type="button" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="cancel_reason">Reason<span class="txt-danger"> *</span></label>
                    <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" required
                        placeholder="e.g. Customer no longer needs the delivery"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Keep Order</button>
                    <button class="btn btn-danger ms-2" type="submit">Cancel Order</button>
                </div>
            </div>
        </form>
    </div>
</div>
