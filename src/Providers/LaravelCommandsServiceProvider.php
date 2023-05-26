<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tkachikov\LaravelCommands\Models\Command;

class LaravelCommandsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'commands');
    }

    public function register(): void
    {
        //
    }
}
