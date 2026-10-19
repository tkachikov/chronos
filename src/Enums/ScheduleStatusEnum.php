<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum ScheduleStatusEnum: string
{
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';

    public function getCssClass(): string
    {
        return match ($this) {
            self::RUNNING => 'text-primary',
            self::SUCCESS => 'text-success',
            self::FAILED => 'text-danger',
            self::SKIPPED => 'text-warning',
        };
    }
}
