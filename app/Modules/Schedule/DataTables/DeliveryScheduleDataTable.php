<?php

declare(strict_types=1);

namespace App\Modules\Schedule\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Schedule\Models\DeliverySchedule;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class DeliveryScheduleDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('customer_name', fn (DeliverySchedule $row): string => e(
                $row->customer?->full_name ?? '—'
            ))
            ->addColumn('pattern', fn (DeliverySchedule $row): string => e($row->patternLabel()))
            ->addColumn('window', fn (DeliverySchedule $row): string => e(
                $row->slot?->label() ?? $row->slotLabel() ?? '—'
            ))
            ->addColumn('driver_name', fn (DeliverySchedule $row): string => e(
                $row->preferredDriver?->full_name ?? 'Assigned by admin'
            ))
            ->addColumn('next_delivery', fn (DeliverySchedule $row): string => $this->nextDelivery($row))
            ->editColumn('status', fn (DeliverySchedule $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.schedule.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (DeliverySchedule $row): string => view('admin.inc.action', [
                'edit' => 'admin.schedule.edit',
                'delete' => 'admin.schedule.destroy',
                'data' => $row,
                'extra' => view('schedule::_partials.row-actions', ['schedule' => $row])->render(),
            ])->render())
            ->rawColumns(['next_delivery', 'status', 'action']);
    }

    public function query(DeliverySchedule $model): QueryBuilder
    {
        $status = request()->input('status');
        $paused = request()->input('paused');
        $customer = request()->input('customer_id');

        return $model->newQuery()
            ->with(['customer:id,first_name,last_name,mobile', 'slot', 'preferredDriver:id,first_name,last_name'])
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)))
            ->when($paused !== null && $paused !== '', fn (QueryBuilder $q) => $q
                ->where('is_paused', filter_var($paused, FILTER_VALIDATE_BOOLEAN)))
            ->when($customer !== null && $customer !== '', fn (QueryBuilder $q) => $q
                ->where('customer_id', (int) $customer))
            ->orderBy('next_run_date')
            ->orderByDesc('id');
    }

    /**
     * A paused routine says so instead of showing a date it will not honour.
     */
    private function nextDelivery(DeliverySchedule $row): string
    {
        if ($row->is_paused) {
            return '<span class="badge badge-light-warning">Paused</span>';
        }

        if ($row->next_run_date === null) {
            return '<span class="badge badge-light-secondary">Finished</span>';
        }

        return e($row->next_run_date->format('D, d M Y'));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('schedule-table')
            ->addColumn(['data' => 'customer_name', 'title' => __('Customer'), 'orderable' => false])
            ->addColumn(['data' => 'pattern', 'title' => __('Pattern'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'window', 'title' => __('Window'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'next_delivery', 'title' => __('Next Delivery'), 'searchable' => false])
            ->addColumn(['data' => 'driver_name', 'title' => __('Usual Driver'), 'orderable' => false])
            ->addColumn(['data' => 'status', 'title' => __('Status'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'action', 'title' => __('Action'), 'orderable' => false, 'searchable' => false])
            ->minifiedAjax()
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
        return 'delivery_schedules_'.date('YmdHis');
    }
}
