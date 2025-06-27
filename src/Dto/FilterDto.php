<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Tkachikov\Chronos\Enums\LastRunStateFilterEnum;
use Tkachikov\Chronos\Enums\RunsInFilterEnum;
use Tkachikov\Chronos\Enums\SchedulersFilterEnum;

final readonly class FilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?RunsInFilterEnum $runsIn = null,
        public ?string $scheduleMethod = null,
        public ?SchedulersFilterEnum $schedulers = null,
        public ?LastRunStateFilterEnum $lastRunState = null
    ) {}
}