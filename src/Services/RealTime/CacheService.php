<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services\RealTime;

use Illuminate\Cache\CacheManager;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\Signals;

final readonly class CacheService
{
    public function __construct(
        private CacheManager $driver,
    ) {
    }

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
    ): ?RunDto {
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

        $this
            ->driver
            ->delete($this->getSignalKey($commandId));
    }

    public function setSignal(
        int $commandId,
        Signals $signal,
    ): void {
        $this
            ->driver
            ->set(
                $this->getSignalKey($commandId),
                $signal->value,
                now()->addMinutes(5),
            );
    }

    public function pullSignal(
        int $commandId,
    ): ?Signals {
        $signal = $this
            ->driver
            ->pull($this->getSignalKey($commandId));

        return is_string($signal)
            ? Signals::tryFrom($signal)
            : null;
    }

    private function getSignalKey(
        int $commandId,
    ): string {
        return $this->getKey($commandId) . '-signal';
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
