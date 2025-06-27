<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Converters;

use Tkachikov\Chronos\Dto\FilterDto;
use Tkachikov\Chronos\Enums\LastRunStateFilterEnum;
use Tkachikov\Chronos\Enums\RunsInFilterEnum;
use Tkachikov\Chronos\Enums\SchedulersFilterEnum;
use Tkachikov\Chronos\Http\Requests\IndexRequest;

final readonly class FilterConverter
{
    public function convert(IndexRequest $request): FilterDto
    {
        return new FilterDto(
            search: $request->get('search'),
            runsIn: $request->enum('runsIn', RunsInFilterEnum::class),
            scheduleMethod: $request->get('scheduleMethod'),
            schedulers: $request->enum('schedulers', SchedulersFilterEnum::class),
            lastRunState: $request->enum('lastRunState', LastRunStateFilterEnum::class),
        );
    }
}