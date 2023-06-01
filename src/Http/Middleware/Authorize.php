<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tkachikov\LaravelPulse\Pulse;
use Illuminate\Http\RedirectResponse;

class Authorize
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        return Pulse::check($request)
            ? $next($request)
            : abort(403);
    }
}
