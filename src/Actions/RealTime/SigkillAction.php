<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTime\StateService;

final readonly class SigkillAction
{
    public function execute(
        Command $command,
    ): void {
        StateService::make($command->id)
            ->sigkill();

        $command
            ->lastRun()
            ->update(['state' => 3]);
    }
}
