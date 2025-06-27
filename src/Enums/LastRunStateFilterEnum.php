<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum LastRunStateFilterEnum: string
{
    case NEVER_RUN = 'Never run';
    case RUNNING = 'Running';
    case SUCCESS = 'Success';
    case FAILED = 'Failed';
}
