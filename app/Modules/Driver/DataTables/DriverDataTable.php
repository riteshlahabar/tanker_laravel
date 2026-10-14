<?php

declare(strict_types=1);

namespace App\Modules\Driver\DataTables;

use App\Models\User;
use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class DriverDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('name', fn (User $row): string => $row->full_name)
            ->addColumn('tanker', fn (User $row): string => $row->currentAssignment?->tanker?->label()
                ?? '<span class="badge badge-light-secondary">No tanker</span>')
            ->editColumn('per_delivery_rate', fn (User $row): string => '₹ '
                .number_format((float) $row->per_delivery_rate, 2))
            ->editColumn('status', fn (User $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.driver.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (User $row): string => view('driver::_partials.row-actions', [
                'driver' => $row,
            ])->render())
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query
                ->whereAny(['first_name', 'last_name'], 'like', "%{$keyword}%"))
            ->rawColumns(['tanker', 'status', 'action']);
    }

    public function query(User $model): QueryBuilder
    {
        $status = request()->input('status');
        $assigned = request()->input('assigned');

        return $model->newQuery()
            ->role(RoleEnum::Driver->value)
            ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number')
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)))
            ->when($assigned === '1', fn (QueryBuilder $q) => $q->whereHas('currentAssignment'))
            ->when($assigned === '0', fn (QueryBuilder $q) => $q->whereDoesntHave('currentAssignment'));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('driver-table')
            ->addColumn(['data' => 'name', 'title' => __('Driver')])
            ->addColumn(['data' => 'mobile', 'title' => __('Mobile')])
            ->addColumn(['data' => 'tanker', 'title' => __('Assigned Tanker'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'per_delivery_rate', 'title' => __('Rate / Delivery')])
            ->addColumn(['data' => 'status', 'title' => __('Status'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'action', 'title' => __('Action'), 'orderable' => false, 'searchable' => false])
            ->minifiedAjax()
            ->orderBy(0)
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
        return 'drivers_'.date('YmdHis');
    }
}
