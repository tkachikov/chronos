<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions;

use Exception;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Services\RealTime\CacheService;

final readonly class InitializeCacheAction
{
    public function __construct(
        private CacheService $cache,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(
        RunDto $dto,
    ): void {
        $alreadyRun = $this
            ->cache
            ->has($dto->commandId);

        if ($alreadyRun) {
            throw new Exception('Already run');
        }

        $this
            ->cache
            ->set($dto);
    }
}