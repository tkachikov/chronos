<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Converters;

use Tkachikov\Chronos\Dto\RealTimeDto;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\Signals;

final readonly class RealTimeConverter
{
    public function convert(
        RealTimeDto $dto,
    ): RunDto {
        return new RunDto(
            commandId: $dto->commandId,
            user: $dto->user,
            args: $dto->args,
            signals: Signals::getSignalsState(),
        );
    }
}