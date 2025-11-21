<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tkachikov\Chronos\ChronosAuthentication;

class Authorize
{
    public function handle(Request $request, Closure $next)
    {
        return ChronosAuthentication::check($request)
            ? $next($request)
            : abort(403);
    }
}
