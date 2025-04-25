<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tkachikov\Chronos\Console\Commands\ChronosAnswerTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosDdTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosDumpTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosOnlyArgumentsTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosOnlyOptionsTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosRunBackgroundCommand;
use Tkachikov\Chronos\Console\Commands\ChronosTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosInstallCommand;
use Tkachikov\Chronos\Console\Commands\ChronosFreeLogsCommand;
use Tkachikov\Chronos\Console\Commands\ChronosIndexUpdateCommand;
use Tkachikov\Chronos\Console\Commands\ChronosUpdateMetricsCommand;
use Tkachikov\Chronos\Console\Commands\ChronosUpdateTimeParamsCommand;

class ChronosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadCommands();
        $this->loadPublishing();
        $this->loadMigrations();
        $this->loadTranslations();
    }

    public function register(): void
    {
        //
    }

    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ChronosInstallCommand::class,
                ChronosIndexUpdateCommand::class,
                ChronosRunBackgroundCommand::class,
                ChronosUpdateTimeParamsCommand::class,
            ]);
        }
        $this->commands([
            ChronosTestCommand::class,
            ChronosDdTestCommand::class,
            ChronosDumpTestCommand::class,
            ChronosFreeLogsCommand::class,
            ChronosAnswerTestCommand::class,
            ChronosUpdateMetricsCommand::class,
            ChronosOnlyOptionsTestCommand::class,
            ChronosOnlyArgumentsTestCommand::class,
        ]);
    }

    public function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    public function loadPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/chronos.php' => config_path('chronos.php'),
            ], 'chronos-config');
            $this->publishes([
                __DIR__ . '/../../stubs/ChronosServiceProvider.stub' => app_path('Providers/ChronosServiceProvider.php'),
            ], 'chronos-provider');
        }
    }

    public function loadRoutes(): void
    {
        Route::middlewareGroup('chronos', config('chronos.middlewares', []));
        Route::group([
            'domain' => config('chronos.domain'),
            'prefix' => 'chronos',
            'middleware' => 'chronos',
            'namespace' => 'Tkachikov\Chronos\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        });
    }

    public function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'chronos');
    }

    public function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'chronos');
    }
}
