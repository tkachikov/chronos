<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Enums;

enum DatabaseEnum: string
{
    case SQLITE = 'sqlite';

    case PGSQL = 'pgsql';

    case MYSQL = 'mysql';
}
