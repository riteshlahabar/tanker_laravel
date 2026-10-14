<?php

declare(strict_types=1);

namespace App\Modules\Tanker\Controllers;

use App\Modules\Tanker\DataTables\TankerDataTable;
use App\Modules\Tanker\Models\Tanker;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TankerListController
{
    /**
     * Renders the page on a normal request and the row payload on the
     * DataTables AJAX request.
     */
    public function __invoke(TankerDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', Tanker::class);

        return $dataTable->render('tanker::index');
    }
}
