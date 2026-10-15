<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class DeliverySlotDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('start_time', fn (DeliverySlot $row): string => $row->startLabel())
            ->editColumn('end_time', fn (DeliverySlot $row): string => $row->endLabel())
            ->editColumn('status', fn (DeliverySlot $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.slot.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (DeliverySlot $row): string => view('admin.inc.action', [
                'edit' => 'admin.slot.edit',
                'delete' => 'admin.slot.destroy',
                'data' => $row,
            ])->render())
            ->rawColumns(['status', 'action']);
    }

    public function query(DeliverySlot $model): QueryBuilder
    {
        $status = request()->input('status');

        return $model->newQuery()
            ->ordered()
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('slot-table')
            ->addColumn(['data' => 'name', 'title' => __('Slot Name')])
            ->addColumn(['data' => 'start_time', 'title' => __('Starts'), 'searchable' => false])
            ->addColumn(['data' => 'end_time', 'title' => __('Ends'), 'searchable' => false])
            ->addColumn(['data' => 'sort_order', 'title' => __('Order'), 'searchable' => false])
            ->addColumn(['data' => 'status', 'title' => __('Status'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'action', 'title' => __('Action'), 'orderable' => false, 'searchable' => false])
            ->minifiedAjax()
            ->orderBy(3)
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
        return 'delivery_slots_'.date('YmdHis');
    }
}
