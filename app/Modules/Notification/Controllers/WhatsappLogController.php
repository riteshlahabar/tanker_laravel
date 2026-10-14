<?php

declare(strict_types=1);

namespace App\Modules\Notification\Controllers;

use App\Modules\Core\Enums\PermissionEnum;
use App\Modules\Notification\DataTables\WhatsappMessageDataTable;
use App\Modules\Notification\Models\WhatsappMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class WhatsappLogController
{
    public function __invoke(WhatsappMessageDataTable $dataTable): View|JsonResponse
    {
        Gate::authorize(PermissionEnum::SettingManage->value);

        return $dataTable->render('notification::index', [
            'driver' => (string) config('services.whatsapp.driver'),
            'templates' => WhatsappMessage::query()
                ->distinct()
                ->orderBy('template')
                ->pluck('template', 'template')
                ->all(),
        ]);
    }
}
