<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Schedule;
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

        /** @var Collection<int, SchedulerHeartbeat> $heartbeats */
        $heartbeats = SchedulerHeartbeat::query()
            ->orderByDesc('last_run_at')
            ->get();

        return $heartbeats;
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
     * The scheduler is stopped when there are enabled schedules, but no server runs schedule:run.
     */
    public function isStopped(): bool
    {
        return $this->databaseHelper->hasTable(SchedulerHeartbeat::class)
            && Schedule::query()->where('run', true)->exists()
            && !$this->isAlive();
    }

    /**
     * In minutes.
     */
    public function getTimeout(): int
    {
        $timeout = config('chronos.monitoring.scheduler_timeout');

        return is_numeric($timeout)
            ? max(1, (int) $timeout)
            : 5;
    }

    public function getHost(): string
    {
        return gethostname() ?: 'unknown';
    }
}
