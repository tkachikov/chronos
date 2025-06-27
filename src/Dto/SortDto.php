<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

final readonly class SortDto
{
    public function __construct(
        public ?string $column = null,
        public ?string $direction = null,
    ) {}
}