<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTime\StateService;

final readonly class SendAnswerAction
{
    public function execute(
        Command $command,
        string $answer,
    ): void {
        $state = StateService::make($command->id);

        if ($state->isPending()) {
            $state->received($answer);
        }
    }
}