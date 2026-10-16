<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The token is passed as a bearer token or the token query parameter.
 */
final class AuthorizeHealthToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) config('chronos.monitoring.health_token');
        $given = (string) ($request->bearerToken() ?? $request->query('token', ''));

        abort_unless(
            $token !== '' && hash_equals($token, $given),
            403,
        );

        return $next($request);
    }
}
