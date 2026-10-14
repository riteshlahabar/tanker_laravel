<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use App\Modules\Auth\Providers\AuthServiceProvider;
use App\Modules\Core\Console\CheckFileLimitsCommand;
use App\Modules\Customer\Providers\CustomerServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\Driver\Providers\DriverServiceProvider;
use App\Modules\Notification\Providers\NotificationServiceProvider;
use App\Modules\Order\Providers\OrderServiceProvider;
use App\Modules\Report\Providers\ReportServiceProvider;
use App\Modules\Setting\Providers\SettingServiceProvider;
use App\Modules\Spot\Providers\SpotServiceProvider;
use App\Modules\Tanker\Providers\TankerServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Registers every module provider and the shared rate limiters.
 */
final class CoreServiceProvider extends ServiceProvider
{
    /**
     * @var array<int, class-string>
     */
    private const MODULE_PROVIDERS = [
        AuthServiceProvider::class,
        DashboardServiceProvider::class,
        TankerServiceProvider::class,
        DriverServiceProvider::class,
        CustomerServiceProvider::class,
        SpotServiceProvider::class,
        OrderServiceProvider::class,
        ReportServiceProvider::class,
        NotificationServiceProvider::class,
        SettingServiceProvider::class,
    ];

    public function register(): void
    {
        foreach (self::MODULE_PROVIDERS as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    public function boot(): void
    {
        $this->registerRateLimiters();

        if ($this->app->runningInConsole()) {
            $this->commands([CheckFileLimitsCommand::class]);
        }
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->input('email', $request->input('mobile', '')).'|'.$request->ip()));

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('exports', fn (Request $request): Limit => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
