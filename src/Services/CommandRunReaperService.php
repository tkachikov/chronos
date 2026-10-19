<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Tkachikov\Chronos\Enums\TypeMessageEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;

/**
 * Finishes runs whose process is gone without finishing them (SIGKILL, OOM, a restart of the server):
 * otherwise they are running forever and hide stuck locks of withoutOverlapping.
 * Runs only of the current server are checked, processes of other servers are not visible.
 */
class CommandRunReaperService
{
    public const KILLED = 3;

    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
    }

    /**
     * @return int The number of finished runs
     */
    public function reap(): int
    {
        if (
            !$this->databaseHelper->hasTable(CommandRun::class)
            || !$this->databaseHelper->hasColumn(CommandRun::class, 'host')
        ) {
            return 0;
        }

        $host = gethostname();

        if (!$host) {
            return 0;
        }

        $reaped = 0;

        CommandRun::query()
            ->where('state', 2)
            ->where('host', $host)
            ->whereNotNull('pid')
            ->get()
            ->each(function (CommandRun $run) use (&$reaped): void {
                if ($this->isAlive((int) $run->pid) !== false) {
                    return;
                }

                $updated = CommandRun::query()
                    ->whereKey($run->id)
                    ->where('state', 2)
                    ->update(['state' => self::KILLED]);

                if (!$updated) {
                    return;
                }

                $reaped++;

                if ($this->databaseHelper->hasTable(CommandLog::class)) {
                    CommandLog::insert([
                        'command_run_id' => $run->id,
                        'type' => TypeMessageEnum::ERROR->value,
                        'message' => "The process $run->pid is gone without finishing the run",
                        'created_at' => now()->format('Y-m-d H:i:s'),
                    ]);
                }
            });

        return $reaped;
    }

    /**
     * Null when it is unknown: neither posix nor /proc are available.
     */
    public function isAlive(int $pid): ?bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            if (posix_kill($pid, 0)) {
                return true;
            }

            // EPERM: the process exists, but belongs to another user
            return posix_get_last_error() === 1;
        }

        if (is_dir('/proc/self')) {
            return is_dir("/proc/$pid");
        }

        return null;
    }
}
