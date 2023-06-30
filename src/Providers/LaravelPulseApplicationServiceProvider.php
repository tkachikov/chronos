<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Tkachikov\LaravelPulse\PulseAuthentication;
use Tkachikov\LaravelPulse\Decorators\IncomeEntryDecorator;

class LaravelPulseApplicationServiceProvider extends ServiceProvider
{
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
    public function boot(): void
    {
        $this->authorization();
        $this->loadTelescopeDecorator();
    }

    /**
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        PulseAuthentication::auth(function ($request) {
            return app()->environment('local')
                || Gate::check('viewPulse', [$request->user]);
        });
    }

    /**
     * @return void
     */
    protected function gate(): void
    {
        Gate::define('viewPulse', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }

    /**
     * @return void
     */
    protected function loadTelescopeDecorator(): void
    {
        if (config('telescope.enabled')) {
            $this->app->extend(IncomingEntry::class, function ($entry) {
                return new IncomeEntryDecorator($entry->content, $entry->uuid);
            });
        }
    }
}
