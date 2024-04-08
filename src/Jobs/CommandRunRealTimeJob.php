<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Tkachikov\Chronos\Services\CommandService;
use Tkachikov\Chronos\Services\CommandRunService;
use Tkachikov\Chronos\Services\ScheduleService;
use Tkachikov\Chronos\Jobs\Middleware\LockMiddleware;
use Tkachikov\Chronos\Models\Command;

class CommandRunRealTimeJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use Dispatchable;
    use SerializesModels;
    use InteractsWithQueue;

    public function __construct(
        private readonly Command $command,
        private readonly string $uuid,
    ) {
    }

    public function handle(
        CommandService $commandService,
        CommandRunService $commandRunService,
    ): void {
        if ($commandService->get($this->command->class)->runInManual()) {
            $commandRunService->run($this->command, $this->uuid);
        }
    }

    public function middleware(): array
    {
        return [
            new LockMiddleware(),
        ];
    }

    public function uniqueId(): string
    {
        return str($this->command->class)
            ->classBasename()
            ->kebab()
            ->toString();
    }

    public function failed(Throwable $e): void
    {
        $service = app(ScheduleService::class);
        $service->updateWaitingRun($this->command->class, $e->getMessage());
    }
}
