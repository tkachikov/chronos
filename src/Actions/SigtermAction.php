<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Services\RealTimeStateService;

final readonly class SigtermAction
{
    public function execute(
        Command $command,
    ): void {
        RealTimeStateService::make($command->id)
            ->sigterm();

        $command
            ->lastRun()
            ->update(['state' => 3]);
    }
}