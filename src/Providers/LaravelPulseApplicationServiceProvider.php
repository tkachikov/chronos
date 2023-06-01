<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Tkachikov\LaravelPulse\Pulse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * @return void
     */
    protected function authorization(): void
    {
        $this->gate();

        Pulse::auth(function ($request) {
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
}
