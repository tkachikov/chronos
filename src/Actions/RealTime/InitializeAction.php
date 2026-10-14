<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Exception;
use Tkachikov\Chronos\Actions\InitializeCacheAction;
use Tkachikov\Chronos\Converters\RealTimeConverter;
use Tkachikov\Chronos\Dto\RealTimeDto;
use Tkachikov\Chronos\Helpers\PhpHelper;

final readonly class InitializeAction
{
    public function __construct(
        private InitializeCacheAction $initializeCacheAction,
        private RealTimeConverter $converter,
        private PhpHelper $phpHelper,
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
            '%s %s %s %d > /dev/null 2>&1 &',
            escapeshellarg($this->phpHelper->getBinary()),
            escapeshellarg(base_path('artisan')),
            'chronos:run-background',
            $dto->commandId,
        ));
    }
}
