<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Tkachikov\Chronos\Dto\RealTimeStateDto;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTime\StateService;

final readonly class GetStateAndFinishRunAction
{
    /**
     * Only logs starting from the offset are returned, the page already has the previous ones.
     */
    public function execute(
        Command $command,
        int $offset = 0,
    ): RealTimeStateDto {
        $state = StateService::make($command->id);

        $stateDto = new RealTimeStateDto(
            logs: array_slice($state->getLogs(), $offset),
            status: $state->getStatus(),
            signals: $state->getSignals(),
            offset: $offset,
        );

        $state->stopRunIfNeeded();

        return $stateDto;
    }
}
