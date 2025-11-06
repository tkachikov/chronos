<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum AnswerState: string
{
    case NotAwaiting = 'not-awaiting';
    case Pending = 'pending';
    case Received = 'received';
}
