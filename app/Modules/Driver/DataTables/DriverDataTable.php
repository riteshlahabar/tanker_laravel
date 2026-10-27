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
            // What one load from this driver's own vehicle costs — the figure
            // every order they deliver is priced from, so it belongs beside
            // the tanker rather than one screen away on Tankers.
            ->addColumn('tanker_rate', fn (User $row): string => $this->rateCell($row))
            ->editColumn('monthly_salary', fn (User $row): string => '₹ '
                .number_format((float) $row->monthly_salary, 2))
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
            ->rawColumns(['tanker', 'tanker_rate', 'status', 'action']);
    }

    public function query(User $model): QueryBuilder
    {
        $status = request()->input('status');
        $assigned = request()->input('assigned');

        return $model->newQuery()
            ->role(RoleEnum::Driver->value)
            // `tanker_rate` is in the select because the Rate / Tanker column
            // reads it — a select narrower than what the table draws is the
            // bug CLAUDE.md's Performance section warns about.
            ->with('currentAssignment.tanker:id,vehicle_name,vehicle_number,tanker_rate')
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
            ->addColumn(['data' => 'tanker_rate', 'title' => __('Rate / Tanker'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'monthly_salary', 'title' => __('Monthly Salary')])
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

    /**
     * Three states, not two: no vehicle at all, a vehicle whose rate the owner
     * has not set yet, and a rate. The middle one reads "Not set" rather than
     * "₹ 0.00" for the same reason an order reads "Not priced yet" — a blank
     * rate means pricing is not configured, not that the water is free.
     */
    private function rateCell(User $row): string
    {
        $tanker = $row->currentAssignment?->tanker;

        if ($tanker === null) {
            return '<span class="f-light">&mdash;</span>';
        }

        return $tanker->tanker_rate === null
            ? '<span class="f-light">Not set</span>'
            : '₹ '.number_format((float) $tanker->tanker_rate, 2);
    }

    protected function filename(): string
    {
        return 'drivers_'.date('YmdHis');
    }
}
