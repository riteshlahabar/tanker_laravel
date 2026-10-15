<?php

declare(strict_types=1);

namespace App\Modules\DeliverySlot\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\DeliverySlot\Contracts\DeliverySlotRepositoryInterface;
use App\Modules\DeliverySlot\Contracts\DeliverySlotServiceInterface;
use App\Modules\DeliverySlot\Models\DeliverySlot;
use App\Modules\DeliverySlot\Policies\DeliverySlotPolicy;
use App\Modules\DeliverySlot\Repositories\DeliverySlotRepository;
use App\Modules\DeliverySlot\Services\DeliverySlotService;

final class DeliverySlotServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'DeliverySlot';
    }

    /**
     * Views are namespaced "slot::" rather than "deliveryslot::" — shorter to
     * read in the Blade files.
     */
    protected function viewNamespace(): string
    {
        return 'slot';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            DeliverySlotRepositoryInterface::class => DeliverySlotRepository::class,
            DeliverySlotServiceInterface::class => DeliverySlotService::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [
            DeliverySlot::class => DeliverySlotPolicy::class,
        ];
    }
}
