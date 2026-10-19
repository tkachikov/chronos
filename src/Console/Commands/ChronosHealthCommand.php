<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Services\HealthService;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosHealthCommand extends Command
{
    protected $signature = 'chronos:health {--json : Output as JSON}';

    protected $description = 'Check that the scheduler is running and schedules have no missed runs and stuck locks';

    public function handle(HealthService $healthService): int
    {
        $health = $healthService->check();

        if ($this->option('json')) {
            $this->line((string) json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $scheduler = $health['scheduler'];

            $this->line(sprintf(
                'Scheduler: %s, last schedule:run: %s',
                $scheduler['status'],
                $scheduler['last_run_at'] ?? 'never',
            ));

            foreach ($scheduler['hosts'] as $host) {
                $this->line(sprintf('  %s: %s', $host['host'], $host['last_run_at']));
            }

            $schedules = $health['schedules'];

            $this->line(sprintf(
                'Schedules: %d enabled, %d missed, %d failed, %d running, %d stuck',
                $schedules['enabled'],
                count($schedules['missed']),
                $schedules['failed'],
                $schedules['running'],
                count($schedules['stuck']),
            ));

            foreach ($schedules['missed'] as $missed) {
                $this->line(sprintf(
                    '  #%d %s: missed since %s',
                    $missed['id'],
                    $missed['name'] ?? $missed['command'] ?? '',
                    $missed['missed_since'],
                ));
            }

            foreach ($schedules['stuck'] as $stuck) {
                $this->line(sprintf(
                    '  #%d %s: stuck lock, skipped %d times since %s, release it by schedule:clear-cache',
                    $stuck['id'],
                    $stuck['name'] ?? $stuck['command'] ?? '',
                    $stuck['skipped'],
                    $stuck['last_run_at'] ?? 'never',
                ));
            }
        }

        $ok = $health['status'] === 'ok';

        if (!$this->option('json')) {
            $ok ? $this->info('OK') : $this->error('FAIL');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
