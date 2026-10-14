<?php

declare(strict_types=1);

namespace App\Modules\Spot\Controllers;

use App\Modules\Spot\DataTables\SpotDataTable;
use App\Modules\Spot\Models\Spot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class SpotListController
{
    public function __invoke(SpotDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize('viewAny', Spot::class);

        return $dataTable->render('spot::index');
    }
}
