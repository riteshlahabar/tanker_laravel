<?php

declare(strict_types=1);

namespace App\Modules\Spot\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Spot\Models\Spot;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class SpotDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('refill_charge', fn (Spot $row): string => '₹ '.number_format((float) $row->refill_charge, 2))
            ->editColumn('address', fn (Spot $row): string => str($row->address ?? '—')->limit(60)->value())
            ->addColumn('coordinates', fn (Spot $row): string => $row->hasCoordinates()
                ? $row->latitude.', '.$row->longitude
                : '<span class="badge badge-light-secondary">Not set</span>')
            ->addColumn('availability', fn (Spot $row): string => $row->is_available
                ? '<span class="badge badge-light-success">Available</span>'
                : '<span class="badge badge-light-danger">Unavailable</span>')
            ->editColumn('status', fn (Spot $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.spot.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (Spot $row): string => view('admin.inc.action', [
                'edit' => 'admin.spot.edit',
                'delete' => 'admin.spot.destroy',
                'data' => $row,
            ])->render())
            ->rawColumns(['coordinates', 'availability', 'status', 'action']);
    }

    public function query(Spot $model): QueryBuilder
    {
        $status = request()->input('status');
        $available = request()->input('is_available');

        return $model->newQuery()
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)))
            ->when($available !== null && $available !== '', fn (QueryBuilder $q) => $q
                ->where('is_available', filter_var($available, FILTER_VALIDATE_BOOLEAN)));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('spot-table')
            ->addColumn(['data' => 'name', 'title' => __('Spot Name')])
            ->addColumn(['data' => 'address', 'title' => __('Address')])
            ->addColumn(['data' => 'refill_charge', 'title' => __('Refill Charge')])
            ->addColumn(['data' => 'coordinates', 'title' => __('Coordinates'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'availability', 'title' => __('Availability'), 'orderable' => false, 'searchable' => false])
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
        return 'spots_'.date('YmdHis');
    }
}
