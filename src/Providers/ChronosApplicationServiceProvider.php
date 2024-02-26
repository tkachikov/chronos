<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Tkachikov\Chronos\ChronosAuthentication;

class ChronosApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->authorization();
    }

    protected function authorization(): void
    {
        $this->gate();

        ChronosAuthentication::auth(function () {
            return app()->environment('local')
                || Gate::check('viewChronos', [Auth::user()]);
        });
    }

    protected function gate(): void
    {
        Gate::define('viewChronos', function ($user) {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
