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
            ->addColumn('reason', $this->reason())
            ->addColumn('action', $this->action())
            ->rawColumns(['body', 'status', 'reason', 'action']);
    }

    /**
     * **The column this whole screen was missing.** `response` has always been
     * recorded, and nothing ever displayed it — so a failure showed as a bare
     * red badge and the owner had no way to tell "WhatsApp is not connected"
     * from a wrong API key. That is why the lost `order.assigned` pair of
     * 2026-10-17 looked like a mystery for days.
     *
     * A delivered row's response says nothing worth a column, so only a
     * message that is still fighting or has given up shows one.
     *
     * @return callable(WhatsappMessage): string
     */
    private function reason(): callable
    {
        return static function (WhatsappMessage $row): string {
            if ($row->status === WhatsappMessage::STATUS_SENT || blank($row->response)) {
                return '<span class="text-muted">—</span>';
            }

            $response = (string) $row->response;

            return sprintf(
                // The full text in the tooltip: a gateway can answer with a
                // stack trace, and 160 characters of it is enough to diagnose
                // without wrecking the table's layout.
                '<span class="text-danger" title="%s">%s</span>',
                e($response),
                e(str($response)->squish()->limit(160)->value()),
            );
        };
    }

    /**
     * @return callable(WhatsappMessage): string
     */
    private function action(): callable
    {
        return static function (WhatsappMessage $row): string {
            if (! $row->isResendable()) {
                return '';
            }

            return view('notification::_partials.resend-button', ['message' => $row])->render();
        };
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
            ->addColumn([
                'data' => 'reason',
                'title' => __('Why it failed'),
                'searchable' => false,
                'orderable' => false,
            ])
            ->addColumn([
                'data' => 'action',
                'title' => __('Action'),
                'searchable' => false,
                'orderable' => false,
            ])
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
