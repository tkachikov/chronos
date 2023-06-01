<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tkachikov\LaravelPulse\Console\Commands\PulseInstallCommand;

class LaravelPulseServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadCommands();
        $this->loadPublishing();
        $this->loadMigrations();
    }

    /**
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * @return void
     */
    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PulseInstallCommand::class,
            ]);
        }
    }

    /**
     * @return void
     */
    public function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    /**
     * @return void
     */
    public function loadPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/pulse.php' => config_path('pulse.php'),
            ], 'pulse-config');
            $this->publishes([
                __DIR__ . '/../../stubs/LaravelPulseServiceProvider.stub' => app_path('Providers/LaravelPulseServiceProvider.php'),
            ], 'pulse-provider');
        }
    }

    /**
     * @return void
     */
    public function loadRoutes(): void
    {
        Route::middlewareGroup('pulse', [
            'web',
            'auth',
            'Tkachikov\LaravelPulse\Http\Middleware\Authorize',
        ]);
        Route::group([
            'domain' => config('pulse.domain'),
            'prefix' => 'pulse',
            'middleware' => 'pulse',
            'namespace' => 'Tkachikov\LaravelPulse\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });
    }

    /**
     * @return void
     */
    public function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'pulse');
    }
}
