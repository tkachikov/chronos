<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

final readonly class FilterDto
{
    public function __construct(
        public ?string $search = null,
    ) {}
}