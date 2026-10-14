<?php

declare(strict_types=1);

namespace App\Modules\Customer\DataTables;

use App\Models\User;
use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Core\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class CustomerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('name', fn (User $row): string => $row->full_name)
            ->editColumn('address', fn (User $row): string => str($row->address ?? '—')->limit(50)->value())
            ->editColumn('created_at', fn (User $row): string => $row->created_at->format('d M Y'))
            ->editColumn('status', fn (User $row): string => view('admin.inc.action', [
                'toggle' => $row,
                'name' => 'status',
                'route' => 'admin.customer.status',
                'value' => (bool) $row->status,
            ])->render())
            ->addColumn('action', fn (User $row): string => view('admin.inc.action', [
                'edit' => 'admin.customer.edit',
                'delete' => 'admin.customer.destroy',
                'data' => $row,
            ])->render())
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query
                ->whereAny(['first_name', 'last_name'], 'like', "%{$keyword}%"))
            ->rawColumns(['status', 'action']);
    }

    public function query(User $model): QueryBuilder
    {
        $status = request()->input('status');

        return $model->newQuery()
            ->role(RoleEnum::Customer->value)
            ->when($status !== null && $status !== '', fn (QueryBuilder $q) => $q
                ->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN)));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('customer-table')
            ->addColumn(['data' => 'name', 'title' => __('Name')])
            ->addColumn(['data' => 'mobile', 'title' => __('Mobile')])
            ->addColumn(['data' => 'address', 'title' => __('Address')])
            ->addColumn(['data' => 'created_at', 'title' => __('Joined'), 'searchable' => false])
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
        return 'customers_'.date('YmdHis');
    }
}
