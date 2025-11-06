<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

final readonly class RealTimeStateDto
{
    public function __construct(
        public array $logs,
        public bool $status,
        public array $signals,
    ) {}
}