<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Tkachikov\Chronos\Enums\ScheduleStatusEnum;
use Tkachikov\Chronos\Models\SchedulerHeartbeat;
use Tkachikov\Chronos\Repositories\ScheduleRepository;

/**
 * Health of the scheduler for external monitoring.
 * Unhealthy when the scheduler is stopped or enabled schedules have missed runs.
 */
class HealthService
{
    public function __construct(
        private readonly SchedulerHeartbeatService $heartbeatService,
        private readonly ScheduleMonitorService $scheduleMonitor,
        private readonly ScheduleRepository $scheduleRepository,
    ) {
    }

    /**
     * @return array{
     *     status: 'ok'|'fail',
     *     scheduler: array{status: 'ok'|'stopped', last_run_at: string|null, timeout_minutes: int, hosts: list<array{host: string, last_run_at: string}>},
     *     schedules: array{enabled: int, missed: list<array{id: int, name: string|null, command: string|null, missed_since: string}>, failed: int, running: int},
     * }
     */
    public function check(): array
    {
        $schedules = $this->scheduleRepository->get();
        $missed = [];

        foreach ($schedules as $schedule) {
            $missedSince = $this->scheduleMonitor->getMissedSince($schedule);

            if ($missedSince) {
                $missed[] = [
                    'id' => (int) $schedule->id,
                    'name' => $schedule->name,
                    'command' => $schedule->command?->class,
                    'missed_since' => $missedSince->toIso8601String(),
                ];
            }
        }

        $stopped = $this->heartbeatService->isStopped();

        return [
            'status' => $stopped || $missed ? 'fail' : 'ok',
            'scheduler' => [
                'status' => $stopped ? 'stopped' : 'ok',
                'last_run_at' => $this->heartbeatService->getLastRunAt()?->toIso8601String(),
                'timeout_minutes' => $this->heartbeatService->getTimeout(),
                'hosts' => $this
                    ->heartbeatService
                    ->get()
                    ->map(fn (SchedulerHeartbeat $heartbeat): array => [
                        'host' => $heartbeat->host,
                        'last_run_at' => $heartbeat->last_run_at->toIso8601String(),
                    ])
                    ->values()
                    ->all(),
            ],
            'schedules' => [
                'enabled' => $schedules->count(),
                'missed' => $missed,
                'failed' => $schedules->where('last_status', ScheduleStatusEnum::FAILED->value)->count(),
                'running' => $schedules->where('last_status', ScheduleStatusEnum::RUNNING->value)->count(),
            ],
        ];
    }
}
