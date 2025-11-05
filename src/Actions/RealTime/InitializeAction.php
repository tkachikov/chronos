<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Actions\RealTime;

use Exception;
use Tkachikov\Chronos\Converters\RealTimeConverter;
use Tkachikov\Chronos\Dto\RealTimeDto;
use Tkachikov\Chronos\Services\RealTime\CacheService;

final readonly class InitializeAction
{
    public function __construct(
        private CacheService $cache,
        private RealTimeConverter $converter,
    ) {}

    /**
     * @throws Exception
     */
    public function execute(
        RealTimeDto $dto,
    ): void {
        $alreadyRun = $this
            ->cache
            ->has($dto->commandId);

        if ($alreadyRun) {
            throw new Exception('Already run');
        }

        $runDto = $this
            ->converter
            ->convert($dto);

        $this
            ->cache
            ->set($runDto);

        \exec(sprintf(
            "php %s %s %d > /dev/null 2>&1 &",
            base_path('artisan'),
            'chronos:run-background',
            $dto->commandId,
        ));
    }
}