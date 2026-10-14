<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\CommandService;

final readonly class EnsureCommandIsAllowed
{
    public function __construct(
        private CommandService $commandService,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $command = $request->route('command');

        if (!$command instanceof Command) {
            $command = Command::find($command);
        }

        abort_unless(
            $command && $this->commandService->exists($command->class),
            404,
        );

        return $next($request);
    }
}
