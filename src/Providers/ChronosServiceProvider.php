<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tkachikov\Chronos\Console\Commands\ChronosAnswerTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosDdTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosDumpTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosFreeLogsCommand;
use Tkachikov\Chronos\Console\Commands\ChronosInstallCommand;
use Tkachikov\Chronos\Console\Commands\ChronosMalformedTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosOnlyArgumentsTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosOnlyOptionsTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosRunBackgroundCommand;
use Tkachikov\Chronos\Console\Commands\ChronosTestCommand;
use Tkachikov\Chronos\Console\Commands\ChronosUpdateMetricsCommand;
use Tkachikov\Chronos\Managers\CommandRunManager;
use Tkachikov\Chronos\Managers\CommandRunManagerInterface;
use Tkachikov\Chronos\Repositories\ArtisanRepository;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Repositories\CommandRepository;
use Tkachikov\Chronos\Repositories\CommandRepositoryInterface;
use Tkachikov\Chronos\Repositories\CommandRunRepository;
use Tkachikov\Chronos\Repositories\CommandRunRepositoryInterface;
use Tkachikov\Chronos\Repositories\TimeRepository;
use Tkachikov\Chronos\Repositories\TimeRepositoryInterface;
use Tkachikov\Chronos\Services\ScheduleService;

class ChronosServiceProvider extends ServiceProvider
{
    public $singletons = [
        TimeRepositoryInterface::class => TimeRepository::class,
        ArtisanRepositoryInterface::class => ArtisanRepository::class,
        CommandRepositoryInterface::class => CommandRepository::class,
        CommandRunRepositoryInterface::class => CommandRunRepository::class,
        CommandRunManagerInterface::class => CommandRunManager::class,
    ];

    public function boot(): void
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadCommands();
        $this->loadPublishing();
        $this->loadMigrations();
        $this->loadTranslations();
        $this->loadServiceProvider();

        if (! $this->app->environment('testing')) {
            $this->loadSingletons();
        }

        $this->loadSchedule();
    }

    public function loadCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ChronosInstallCommand::class,
                ChronosRunBackgroundCommand::class,
            ]);
        }
        $this->commands([
            ChronosTestCommand::class,
            ChronosDdTestCommand::class,
            ChronosDumpTestCommand::class,
            ChronosFreeLogsCommand::class,
            ChronosAnswerTestCommand::class,
            ChronosUpdateMetricsCommand::class,
            ChronosMalformedTestCommand::class,
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

    public function loadServiceProvider(): void
    {
        $this
            ->app
            ->booted(function () {
                if (file_exists(app_path('Providers/ChronosServiceProvider.php'))) {
                    $this
                        ->app
                        ->register('App\\Providers\\ChronosServiceProvider');
                } else {
                    $this
                        ->app
                        ->register(ChronosApplicationServiceProvider::class);
                }
            });
    }

    public function loadSchedule(): void
    {
        $this
            ->app
            ->booted(function () {
                $scheduler = $this
                    ->app
                    ->make(Schedule::class);

                $this
                    ->app
                    ->make(ScheduleService::class)
                    ->schedule($scheduler);
            });
    }

    public function loadSingletons(): void
    {
        $this
            ->app
            ->booted(function () {
                $this
                    ->app
                    ->make(ArtisanRepositoryInterface::class)
                    ->load();

                $this
                    ->app
                    ->make(CommandRepositoryInterface::class)
                    ->load();

                $this
                    ->app
                    ->make(CommandRunManagerInterface::class)
                    ->load();
            });
    }
}
