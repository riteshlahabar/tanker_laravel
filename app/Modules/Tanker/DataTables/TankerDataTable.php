<?php

declare(strict_types=1);

namespace App\Modules\Tanker\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class TankerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('capacity_litres', fn (Tanker $row): string => $row->capacity_litres
                ? number_format((float) $row->capacity_litres).' L'
                : '—')
            ->editColumn('tanker_rate', fn (Tanker $row): string => $row->tanker_rate === null
                ? '<span class="f-light">default</span>'
                : '₹ '.number_format((float) $row->tanker_rate, 2))
            ->editColumn('insurance_expiry', fn (Tanker $row): string => $row->insurance_expiry
                ?->format('d M Y') ?? '—')
            ->addColumn('driver', fn (Tanker $row): string => $row->currentAssignment?->driver?->full_name
                ?? '<span class="badge badge-light-secondary">Unassigned</span>')
            ->editColumn('status', fn (Tanker $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.tanker.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (Tanker $row): string => view('admin.inc.action', [
                'edit' => 'admin.tanker.edit',
                'delete' => 'admin.tanker.destroy',
                'data' => $row,
            ])->render())
            ->rawColumns(['tanker_rate', 'driver', 'status', 'action']);
    }

    /**
     * Eager-loads the open assignment and its driver, so the driver column
     * costs two queries for the page rather than one per row.
     */
    public function query(Tanker $model): QueryBuilder
    {
        $status = request()->input('status');

        return $model->newQuery()
            ->with('currentAssignment.driver:id,first_name,last_name')
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('tanker-table')
            ->addColumn(['data' => 'vehicle_name', 'title' => __('Vehicle Name')])
            ->addColumn(['data' => 'vehicle_number', 'title' => __('Vehicle Number')])
            ->addColumn(['data' => 'capacity_litres', 'title' => __('Capacity')])
            ->addColumn(['data' => 'tanker_rate', 'title' => __('Rate / Tanker'), 'searchable' => false])
            ->addColumn(['data' => 'driver', 'title' => __('Current Driver'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'insurance_expiry', 'title' => __('Insurance Expiry'), 'searchable' => false])
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
        return 'tankers_'.date('YmdHis');
    }
}
