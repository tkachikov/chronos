<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Converters;

use Tkachikov\Chronos\Dto\SortDto;
use Tkachikov\Chronos\Http\Requests\IndexRequest;

final readonly class SortConverter
{
    public function convert(IndexRequest $request): SortDto
    {
        return new SortDto(
            column: $request->get('sortKey'),
            direction: $request->get('sortBy'),
        );
    }
}
