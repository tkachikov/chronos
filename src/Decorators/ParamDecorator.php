<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

final readonly class ParamDecorator
{
    public function __construct(
        public string $title,
        public ?string $default = null,
    ) {}
}