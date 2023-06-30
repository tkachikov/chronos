<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Tkachikov\LaravelPulse\PulseAuthentication;

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

        PulseAuthentication::auth(function () {
            return app()->environment('local')
                || Gate::check('viewPulse', [Auth::user()]);
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
