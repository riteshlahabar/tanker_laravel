<?php

declare(strict_types=1);

namespace App\Modules\Notification\DataTables;

use App\Modules\Core\DataTables\DataTableDefaults;
use App\Modules\Notification\Models\WhatsappMessage;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;

final class WhatsappMessageDataTable extends DataTable
{
    /**
     * @var array<string, string>
     */
    private const BADGES = [
        WhatsappMessage::STATUS_SENT => 'badge-light-success',
        WhatsappMessage::STATUS_FAILED => 'badge-light-danger',
        WhatsappMessage::STATUS_PENDING => 'badge-light-warning',
    ];

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('body', fn (WhatsappMessage $row): string => nl2br(e(
                str($row->body ?? '')->limit(140)->value(),
            )))
            ->editColumn('status', fn (WhatsappMessage $row): string => sprintf(
                '<span class="badge %s">%s</span>',
                self::BADGES[$row->status] ?? 'badge-light-secondary',
                ucfirst((string) $row->status),
            ))
            ->editColumn('created_at', fn (WhatsappMessage $row): string => $row->created_at->format('d M Y, H:i'))
            ->editColumn('sent_at', fn (WhatsappMessage $row): string => $row->sent_at?->format('d M Y, H:i') ?? '—')
            ->rawColumns(['body', 'status']);
    }

    public function query(WhatsappMessage $model): QueryBuilder
    {
        $request = request();

        return $model->newQuery()
            ->when($request->filled('status'), fn (QueryBuilder $q) => $q
                ->where('status', $request->input('status')))
            ->when($request->filled('template'), fn (QueryBuilder $q) => $q
                ->where('template', $request->input('template')));
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('whatsapp-table')
            ->addColumn(['data' => 'created_at', 'title' => __('Queued'), 'searchable' => false])
            ->addColumn(['data' => 'to', 'title' => __('To')])
            ->addColumn(['data' => 'template', 'title' => __('Template')])
            ->addColumn(['data' => 'body', 'title' => __('Message')])
            ->addColumn(['data' => 'status', 'title' => __('Status'), 'searchable' => false])
            ->addColumn(['data' => 'attempts', 'title' => __('Tries'), 'searchable' => false])
            ->addColumn(['data' => 'sent_at', 'title' => __('Sent'), 'searchable' => false])
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
        return 'whatsapp_log_'.date('YmdHis');
    }
}
