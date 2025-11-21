<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Jobs\Middleware;

use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class LockMiddleware
{
    protected string $keyLock;

    /**
     * @description Use blocking in parallels processes in Horizon
     *
     * @throws InvalidArgumentException
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

    public function initKeyLock($job): void
    {
        $prefix = method_exists($job, 'uniqueId')
            ? '-' . $job->uniqueId()
            : '';
        $this->keyLock = 'lock-jobs-' . $job::class . $prefix;
    }
}
