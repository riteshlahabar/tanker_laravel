<?php

declare(strict_types=1);

namespace App\Modules\Core\DataTables;

/**
 * Shared DataTables options so every list in the panel behaves identically:
 * server-side paging, the same empty-state wording, and feather icons redrawn
 * after each draw (the action column renders feather <i> tags).
 */
final class DataTableDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function parameters(): array
    {
        return [
            'processing' => true,
            'serverSide' => true,
            'responsive' => true,
            'pageLength' => 25,
            'lengthMenu' => [[10, 25, 50, 100], [10, 25, 50, 100]],
            'language' => [
                'emptyTable' => 'No records found',
                'zeroRecords' => 'No records found',
                'infoEmpty' => '',
                'search' => '',
                'searchPlaceholder' => 'Search...',
            ],
            'drawCallback' => 'function (settings) {
                if (settings._iRecordsDisplay === 0) {
                    $(settings.nTableWrapper).find(".dataTables_paginate").hide();
                } else {
                    $(settings.nTableWrapper).find(".dataTables_paginate").show();
                }
                feather.replace();
            }',
        ];
    }
}
