<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ChronosCommand
{
    public function __construct(
        public bool $notRunInManual = false,
        public bool $notRunInSchedule = false,
    ) {}
}