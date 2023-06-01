<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelPulseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadRoutes();
        $this->loadViews();
    }

    public function register(): void
    {
        //
    }

    public function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    public function loadRoutes(): void
    {
        Route::middlewareGroup('pulse', ['web', 'auth']);
        Route::group([
            'domain' => null,
            'prefix' => 'pulse',
            'middleware' => 'pulse',
            'namespace' => 'Tkachikov\LaravelPulse\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });
    }

    public function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'pulse');
    }
}
