<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Converters;

use Tkachikov\Chronos\Dto\RealTimeDto;
use Tkachikov\Chronos\Dto\RealTimeRunDto;
use Tkachikov\Chronos\Enums\Signals;

final readonly class RealTimeConverter
{
    public function convert(
        RealTimeDto $dto,
    ): RealTimeRunDto {
        return new RealTimeRunDto(
            userId: $dto->userId,
            commandId: $dto->commandId,
            args: $dto->args,
            signals: Signals::getSignalsState(),
        );
    }
}