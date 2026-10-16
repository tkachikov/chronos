<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\SchedulerHeartbeat;

/**
 * Every schedule:run of each server is saved, so it is visible when the scheduler stops (e.g. cron is broken).
 */
class SchedulerHeartbeatService
{
    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    public function beat(): void
    {
        if (!$this->databaseHelper->hasTable(SchedulerHeartbeat::class)) {
            return;
        }

        SchedulerHeartbeat::query()->updateOrCreate(
            ['host' => $this->getHost()],
            ['last_run_at' => now()],
        );
    }

    /**
     * @return Collection<int, SchedulerHeartbeat>
     */
    public function get(): Collection
    {
        if (!$this->databaseHelper->hasTable(SchedulerHeartbeat::class)) {
            return collect();
        }

        return SchedulerHeartbeat::query()
            ->orderByDesc('last_run_at')
            ->get();
    }

    public function getLastRunAt(): ?Carbon
    {
        return $this->get()->first()?->last_run_at;
    }

    /**
     * The scheduler is alive when any server has run schedule:run within the timeout.
     */
    public function isAlive(): bool
    {
        $lastRunAt = $this->getLastRunAt();

        return $lastRunAt !== null
            && $lastRunAt->greaterThanOrEqualTo(now()->subMinutes($this->getTimeout()));
    }

    /**
     * In minutes.
     */
    public function getTimeout(): int
    {
        return max(1, (int) config('chronos.monitoring.scheduler_timeout', 5));
    }

    public function getHost(): string
    {
        return gethostname() ?: 'unknown';
    }
}
