<?php

declare(strict_types=1);

namespace App\Modules\Expense\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Expense\Models\DriverExpense;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class ExpenseDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('driver', fn (DriverExpense $row): string => $row->driver?->full_name ?? '—')
            ->addColumn('tanker', fn (DriverExpense $row): string => $row->tanker?->label()
                ?? '<span class="f-light">—</span>')
            ->editColumn('expense_date', fn (DriverExpense $row): string => $row->expense_date?->format('d M Y') ?? '—')
            ->editColumn('category', fn (DriverExpense $row): string => sprintf(
                '<span class="badge %s">%s</span>',
                $row->category?->badge() ?? 'badge-light-dark',
                e($row->category?->label() ?? '—'),
            ))
            ->editColumn('amount', fn (DriverExpense $row): string => '₹ '.number_format((float) $row->amount, 2))
            // The whole reason created_by exists: showing at a glance that the
            // office filled this in because the driver did not.
            ->addColumn('entered_by', fn (DriverExpense $row): string => $row->enteredByAdmin()
                ? '<span class="badge badge-light-warning">Office</span>'
                : '<span class="f-light">Driver</span>')
            ->addColumn('action', fn (DriverExpense $row): string => view('expense::_partials.row-actions', [
                'expense' => $row,
            ])->render())
            ->filterColumn('driver', fn (QueryBuilder $query, string $keyword) => $query
                ->whereHas('driver', fn (QueryBuilder $q) => $q
                    ->whereAny(['first_name', 'last_name'], 'like', "%{$keyword}%")))
            ->rawColumns(['tanker', 'category', 'entered_by', 'action']);
    }

    public function query(DriverExpense $model): QueryBuilder
    {
        $driverId = request()->input('driver_id');
        $category = request()->input('category');
        $from = request()->input('from');
        $to = request()->input('to');

        return $model->newQuery()
            ->with(['driver:id,first_name,last_name', 'tanker:id,vehicle_name,vehicle_number'])
            ->between(is_string($from) && $from !== '' ? $from : null, is_string($to) && $to !== '' ? $to : null)
            ->when($driverId !== null && $driverId !== '', fn (QueryBuilder $q) => $q
                ->where('driver_id', (int) $driverId))
            ->when($category !== null && $category !== '', fn (QueryBuilder $q) => $q
                ->where('category', $category));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('expense-table')
            ->addColumn(['data' => 'expense_date', 'title' => __('Date')])
            ->addColumn(['data' => 'driver', 'title' => __('Driver')])
            ->addColumn(['data' => 'category', 'title' => __('Category')])
            ->addColumn(['data' => 'amount', 'title' => __('Amount')])
            ->addColumn(['data' => 'tanker', 'title' => __('Tanker'), 'orderable' => false, 'searchable' => false])
            ->addColumn(['data' => 'entered_by', 'title' => __('Added By'), 'orderable' => false, 'searchable' => false])
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
        return 'driver_expenses_'.date('YmdHis');
    }
}
