<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Tkachikov\Chronos\Enums\RunsInEnum;

final readonly class FilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?RunsInEnum $runsIn = null,
    ) {}
}