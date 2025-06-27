<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Converters;

use Tkachikov\Chronos\Dto\FilterDto;
use Tkachikov\Chronos\Http\Requests\IndexRequest;

final readonly class FilterConverter
{
    public function convert(IndexRequest $request): FilterDto
    {
        return new FilterDto(
            search: $request->get('search'),
        );
    }
}