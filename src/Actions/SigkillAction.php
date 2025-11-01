<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTimeStateService;

final readonly class SigkillAction
{
    public function execute(
        Command $command,
    ): void {
        RealTimeStateService::make($command->id)
            ->sigkill();

        $command
            ->lastRun()
            ->update(['state' => 3]);
    }
}