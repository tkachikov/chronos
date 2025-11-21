<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Illuminate\Database\Eloquent\Model;

final readonly class RealTimeDto
{
    public function __construct(
        public int $commandId,
        public array $args,
        public ?Model $user = null,
    ) {
    }
}
