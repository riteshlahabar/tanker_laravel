<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Notification\Console\SendDailyReportCommand;
use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Gateways\LogGateway;
use App\Modules\Notification\Gateways\NodeApiGateway;
use App\Modules\Notification\Listeners\SendOrderStatusNotification;
use App\Modules\Notification\Services\WhatsAppDispatcher;
use App\Modules\Order\Events\OrderStatusChanged;

final class NotificationServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Notification';
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [
            WhatsAppDispatcherInterface::class => WhatsAppDispatcher::class,
        ];
    }

    /**
     * @return array<class-string, array<int, class-string>>
     */
    protected function events(): array
    {
        return [
            OrderStatusChanged::class => [SendOrderStatusNotification::class],
        ];
    }

    public function register(): void
    {
        parent::register();

        // The gateway is chosen by config, which is what makes swapping in the
        // Node service a .env change rather than a code change.
        $this->app->bind(WhatsAppGatewayInterface::class, static function (): WhatsAppGatewayInterface {
            if (config('services.whatsapp.driver') !== 'node') {
                return new LogGateway;
            }

            return new NodeApiGateway(
                url: (string) config('services.whatsapp.url'),
                token: config('services.whatsapp.token'),
                timeout: (int) config('services.whatsapp.timeout', 15),
            );
        });
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([SendDailyReportCommand::class]);
        }
    }
}
