<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse;

use Closure;

class Pulse
{
    public static Closure $auth;

    /**
     * @param $callable
     *
     * @return static
     */
    public static function auth($callable): static
    {
        static::$auth = $callable;

        return new static;
    }

    /**
     * @param $request
     *
     * @return bool
     */
    public static function check($request): bool
    {
        return (static::$auth ?: function () {
            return app()->environment('local');
        })($request);
    }
}
