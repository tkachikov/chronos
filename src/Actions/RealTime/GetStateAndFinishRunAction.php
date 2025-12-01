<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Tkachikov\Chronos\Dto\RealTimeStateDto;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTime\StateService;

final readonly class GetStateAndFinishRunAction
{
    public function execute(
        Command $command,
    ): RealTimeStateDto {
        $state = StateService::make($command->id);

        $stateDto = new RealTimeStateDto(
            logs: $state->getLogs(),
            status: $state->getStatus(),
            signals: $state->getSignals(),
        );

        $state->stopRunIfNeeded();

        return $stateDto;
    }
}
