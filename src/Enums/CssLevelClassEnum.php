<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands\Enums;

enum CssLevelClassEnum: string
{
    case SECONDARY = 'secondary';

    case PRIMARY = 'primary';

    case WARNING = 'warning';

    case DANGER = 'danger';
}
