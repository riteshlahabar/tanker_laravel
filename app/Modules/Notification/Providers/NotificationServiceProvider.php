<?php

declare(strict_types=1);

namespace App\Modules\Notification\Providers;

use App\Modules\Core\Providers\ModuleServiceProvider;
use App\Modules\Notification\Console\SendDailyReportCommand;
use App\Modules\Notification\Contracts\WhatsAppDispatcherInterface;
use App\Modules\Notification\Contracts\WhatsAppGatewayInterface;
use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use App\Modules\Notification\Gateways\LogGateway;
use App\Modules\Notification\Gateways\NodeApiGateway;
use App\Modules\Notification\Gateways\ScanApiGateway;
use App\Modules\Notification\Listeners\SendOrderStatusNotification;
use App\Modules\Notification\Listeners\SendScheduleRestoreNotification;
use App\Modules\Notification\Listeners\SendScheduleSkipNotification;
use App\Modules\Notification\Services\WhatsAppDispatcher;
use App\Modules\Notification\Services\WhatsAppScanClient;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Schedule\Events\ScheduleRestored;
use App\Modules\Schedule\Events\ScheduleSkipped;

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
            // Standing orders announce themselves the same way: the Schedule
            // module raises an event and knows nothing about WhatsApp.
            ScheduleSkipped::class => [SendScheduleSkipNotification::class],
            ScheduleRestored::class => [SendScheduleRestoreNotification::class],
        ];
    }

    public function register(): void
    {
        parent::register();

        // The gateway is chosen by config, which is what makes swapping in the
        // Node service a .env change rather than a code change.
        $this->app->singleton(WhatsAppScanClientInterface::class, static fn (): WhatsAppScanClient => new WhatsAppScanClient(
            baseUrl: (string) config('services.whatsapp_scan.base_url'),
            apiKey: (string) config('services.whatsapp_scan.api_key'),
            timeout: (int) config('services.whatsapp_scan.timeout', 60),
        ));

        $this->app->bind(WhatsAppGatewayInterface::class, function (): WhatsAppGatewayInterface {
            return match (config('services.whatsapp.driver')) {
                // The owner's QR-scan service: {phone, message} behind an
                // x-api-key header.
                'scan' => new ScanApiGateway($this->app->make(WhatsAppScanClientInterface::class)),
                // A generic endpoint taking the whole message envelope.
                'node' => new NodeApiGateway(
                    url: (string) config('services.whatsapp.url'),
                    token: config('services.whatsapp.token'),
                    timeout: (int) config('services.whatsapp.timeout', 15),
                ),
                default => new LogGateway,
            };
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
