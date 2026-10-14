<?php

declare(strict_types=1);

namespace App\Modules\Order\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Order\Contracts\OrderRepositoryInterface;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class OrderDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('customer', fn (Order $row): string => $row->customer?->full_name ?? '—')
            ->addColumn('driver', fn (Order $row): string => $row->driver?->full_name
                ?? '<span class="badge badge-light-warning">Unassigned</span>')
            ->addColumn('tanker', fn (Order $row): string => $row->tanker?->vehicle_number ?? '—')
            ->addColumn('spot', fn (Order $row): string => $row->spot?->name ?? '—')
            ->editColumn('order_amount', fn (Order $row): string => '₹ '.number_format((float) $row->order_amount, 2))
            ->editColumn('delivery_date', fn (Order $row): string => $row->delivery_date?->format('d M Y') ?? '—')
            ->editColumn('status', fn (Order $row): string => sprintf(
                '<span class="badge %s">%s</span>',
                $row->status->badge(),
                $row->status->label(),
            ))
            ->addColumn('action', fn (Order $row): string => view('order::_partials.row-actions', [
                'order' => $row,
            ])->render())
            ->filterColumn('customer', fn (QueryBuilder $query, string $keyword) => $query
                ->whereHas('customer', fn (QueryBuilder $q) => $q
                    ->whereAny(['first_name', 'last_name', 'mobile'], 'like', "%{$keyword}%")))
            ->rawColumns(['driver', 'status', 'action']);
    }

    /**
     * Relations are eager-loaded so a 100-row page stays at a handful of
     * queries rather than one per row.
     */
    public function query(Order $model): QueryBuilder
    {
        $request = request();

        return $this->visibleQuery($model)
            ->with([
                'customer:id,first_name,last_name,mobile',
                'driver:id,first_name,last_name',
                'tanker:id,vehicle_number',
                'spot:id,name',
            ])
            ->when($request->filled('status'), fn (QueryBuilder $q) => $q
                ->where('status', $request->input('status')))
            ->when($request->filled('driver_id'), fn (QueryBuilder $q) => $q
                ->where('driver_id', $request->input('driver_id')))
            ->when($request->filled('from'), fn (QueryBuilder $q) => $q
                ->whereDate('delivery_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn (QueryBuilder $q) => $q
                ->whereDate('delivery_date', '<=', $request->input('to')));
    }

    /**
     * Scoped through the repository, so a driver's table can only ever contain
     * their own orders.
     */
    private function visibleQuery(Order $model): QueryBuilder
    {
        $user = request()->user();

        return $user === null
            ? $model->newQuery()
            : app(OrderRepositoryInterface::class)->visibleTo($user);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('order-table')
            ->addColumn(['data' => 'order_no', 'title' => __('Order No')])
            ->addColumn(['data' => 'customer', 'title' => __('Customer')])
            ->addColumn(['data' => 'delivery_date', 'title' => __('Delivery Date')])
            ->addColumn(['data' => 'driver', 'title' => __('Driver'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'tanker', 'title' => __('Tanker'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'spot', 'title' => __('Refill Spot'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'order_amount', 'title' => __('Amount')])
            ->addColumn(['data' => 'status', 'title' => __('Status'), 'searchable' => false])
            ->addColumn(['data' => 'action', 'title' => __('Action'), 'orderable' => false, 'searchable' => false])
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters(DataTableDefaults::parameters());
    }

    /**
     * @return array<int, mixed>
     */
    public function getColumns(): array
    {
        return [];
    }

    protected function filename(): string
    {
        return 'orders_'.date('YmdHis');
    }
}
