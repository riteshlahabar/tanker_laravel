<?php

declare(strict_types=1);

namespace App\Modules\Core\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base for every module provider. A module declares its interface bindings and
 * policies; routes and views are discovered from the module folder, so adding a
 * module never means editing a central file twice.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Module folder name, e.g. "Order". Also the view namespace in lowercase.
     */
    abstract protected function name(): string;

    /**
     * Interface => concrete implementation.
     *
     * @return array<class-string, class-string>
     */
    protected function bindings(): array
    {
        return [];
    }

    /**
     * Model => policy.
     *
     * @return array<class-string, class-string>
     */
    protected function policies(): array
    {
        return [];
    }

    public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Route parameter => resolver. Use this when the default binding would be
     * too broad — "{driver}" must resolve only to a user holding the driver
     * role, never to any user id someone types into the URL.
     *
     * @return array<string, \Closure(string): mixed>
     */
    protected function routeBindings(): array
    {
        return [];
    }

    /**
     * Event => listeners this module reacts to. Lets a module subscribe to
     * another module's event without either one depending on the other.
     *
     * @return array<class-string, array<int, class-string>>
     */
    protected function events(): array
    {
        return [];
    }

    public function boot(): void
    {
        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->routeBindings() as $parameter => $resolver) {
            Route::bind($parameter, $resolver);
        }

        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        $this->loadModuleViews();
        $this->loadModuleRoutes();
    }

    private function loadModuleViews(): void
    {
        $views = $this->path('Resources/views');

        if (is_dir($views)) {
            $this->loadViewsFrom($views, strtolower($this->name()));
        }
    }

    private function loadModuleRoutes(): void
    {
        $web = $this->path('Routes/web.php');

        if (is_file($web)) {
            Route::middleware('web')->group($web);
        }

        $api = $this->path('Routes/api.php');

        if (is_file($api)) {
            Route::middleware('api')->prefix('api')->group($api);
        }
    }

    private function path(string $suffix): string
    {
        return app_path("Modules/{$this->name()}/{$suffix}");
    }
}
