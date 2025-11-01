<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTimeStateService;

final readonly class RealTimeSendAnswerAction
{
    public function execute(
        Command $command,
        string $answer,
    ): void {
        $state = RealTimeStateService::make($command->id);

        if ($state->isPending()) {
            $state->received($answer);
        }
    }
}