<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum RunsInEnum: string
{
    case ALL = 'All';
    case MANUAL_ON = 'Manual on';
    case MANUAL_OFF = 'Manual off';
    case SCHEDULE_ON = 'Schedule on';
    case SCHEDULE_OFF = 'Schedule off';
}
