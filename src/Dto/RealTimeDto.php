<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

final readonly class RealTimeDto
{
    public function __construct(
        public int $userId,
        public int $commandId,
        public array $args,
    ) {}
}