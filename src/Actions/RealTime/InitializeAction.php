<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Exception;
use Tkachikov\Chronos\Actions\InitializeCacheAction;
use Tkachikov\Chronos\Converters\RealTimeConverter;
use Tkachikov\Chronos\Dto\RealTimeDto;

final readonly class InitializeAction
{
    public function __construct(
        private InitializeCacheAction $initializeCacheAction,
        private RealTimeConverter $converter,
    ) {
    }

    /**
     * @throws Exception
     */
    public function execute(
        RealTimeDto $dto,
    ): void {
        $runDto = $this
            ->converter
            ->convert($dto);

        $this
            ->initializeCacheAction
            ->execute($runDto);

        \exec(sprintf(
            'php %s %s %d > /dev/null 2>&1 &',
            base_path('artisan'),
            'chronos:run-background',
            $dto->commandId,
        ));
    }
}
