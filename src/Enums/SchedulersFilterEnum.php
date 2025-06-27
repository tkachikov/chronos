<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum SchedulersFilterEnum: string
{
    case ALL = 'All';
    case MISSING = 'Missing';
    case HAS_ONE = 'Has one';
    case HAS_MANY = 'Has many';
    case SOME_OFF = 'Some off';
    case ALL_OFF = 'All off';
}
