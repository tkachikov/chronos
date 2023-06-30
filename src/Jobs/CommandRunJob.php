<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Tkachikov\LaravelPulse\Services\CommandService;
use Tkachikov\LaravelPulse\Services\ScheduleService;
use Tkachikov\LaravelPulse\Jobs\Middleware\LockMiddleware;

class CommandRunJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;
    use Dispatchable;
    use SerializesModels;
    use InteractsWithQueue;

    /**
     * @return void
     */
    public function __construct(
        private readonly string $class,
        private readonly array $args = [],
    ) {
    }

    /**
     * @param ScheduleService $scheduleService
     *
     * @throws mixed
     *
     * @return void
     */
    public function handle(CommandService $commandService): void
    {
        if ($commandService->get($this->class)->runInManual()) {
            Artisan::call($this->class, $this->args);
        }
    }

    /**
     * @return array
     */
    public function middleware(): array
    {
        return [
            new LockMiddleware(),
        ];
    }

    /**
     * @return string
     */
    public function uniqueId(): string
    {
        return str($this->class)
            ->classBasename()
            ->kebab()
            ->toString();
    }

    /**
     * @param Throwable $e
     *
     * @return void
     */
    public function failed(Throwable $e): void
    {
        /** @var ScheduleService $service */
        $service = app(ScheduleService::class);
        $service->updateWaitingRun($this->class, $e->getMessage());
    }
}
