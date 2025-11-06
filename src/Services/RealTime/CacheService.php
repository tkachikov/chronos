<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services\RealTime;

use Illuminate\Cache\CacheManager;
use Tkachikov\Chronos\Dto\RunDto;

final readonly class CacheService
{
    public function __construct(
        private CacheManager $driver,
    ) {}

    public function has(
        int $commandId,
    ): bool {
        $key = $this->getKey($commandId);

        return $this
            ->driver
            ->has($key);
    }

    public function set(
        RunDto $dto,
    ): void {
        $key = $this->getKey($dto->commandId);

        $this
            ->driver
            ->set(
                $key,
                $dto,
                now()->addMinutes(5),
            );
    }

    public function get(
        int $commandId,
    ): RunDto {
        $key = $this->getKey($commandId);

        return $this
            ->driver
            ->get($key);
    }

    public function delete(
        int $commandId,
    ): void {
        $key = $this->getKey($commandId);

        $this
            ->driver
            ->delete($key);
    }

    private function getKey(
        int $commandId,
    ): string {
        return sprintf(
            'chronos-commands-%d',
            $commandId,
        );
    }
}