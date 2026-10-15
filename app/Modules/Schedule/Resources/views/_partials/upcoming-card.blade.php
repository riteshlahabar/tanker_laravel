{{-- The next fortnight. The Skip button is disabled with its reason when the
     rules refuse it, so the page never offers an action that would fail. --}}
<div class="card">
    <div class="card-header"><h4 class="mb-0">Next Deliveries</h4></div>
    <div class="card-body">
        <div class="table-responsive custom-scrollbar">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Due</th>
                        <th>Order</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($upcoming as $date)
                        @include('schedule::_partials.upcoming-row', ['date' => $date])
                    @empty
                        <tr><td colspan="4" class="text-center f-m-light">No deliveries are due.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
