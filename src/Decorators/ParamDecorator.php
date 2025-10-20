<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use Tkachikov\Chronos\Enums\TimeHelp;

final readonly class ParamDecorator
{
    public function __construct(
        public string    $title,
        public ?string   $default = null,
        public ?TimeHelp $help = null,
    ) {}
}