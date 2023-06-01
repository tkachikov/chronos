<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Jobs\Middleware;

use Throwable;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

class LockMiddleware
{
    protected string $keyLock;

    /**
     * Use blocking in parallels processes in Horizon
     *
     * @param $job
     * @param $next
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function handle($job, $next): mixed
    {
        $this->initKeyLock($job);
        $lock = Cache::lock($this->keyLock, 60);
        if (!$lock->get()) {
            return null;
        }
        try {
            return $next($job);
        } catch (Throwable $e) {
            report($e);
        } finally {
            $lock->release();
        }

        return null;
    }

    /**
     * @param $job
     *
     * @return void
     */
    public function initKeyLock($job): void
    {
        $prefix = method_exists($job, 'uniqueId')
            ? $job->uniqueId()
            : '';
        $this->keyLock = str('lock_jobs_')
            ->append($job::class)
            ->append($prefix)
            ->toString();
    }
}
