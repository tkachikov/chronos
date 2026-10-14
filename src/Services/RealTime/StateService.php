<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services\RealTime;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\AnswerState;
use Tkachikov\Chronos\Enums\Signals;

final readonly class StateService
{
    public function __construct(
        public RunDto $dto,
        private CacheService $cache,
    ) {
    }

    public static function make(
        int $commandId,
    ): self {
        /** @var CacheService $cache */
        $cache = app(CacheService::class);
        $dto = $cache->get($commandId)
            ?? throw new RuntimeException('Command is not running');

        return new self($dto, $cache);
    }

    /**
     * If the state has expired in the cache (e.g. the command is silent for a long time), it is restored.
     */
    public function refresh(): self
    {
        $dto = $this
            ->cache
            ->get($this->dto->commandId);

        if ($dto === null) {
            $this
                ->cache
                ->set($this->dto);

            return $this;
        }

        return new self($dto, $this->cache);
    }

    public function getArgs(): array
    {
        return $this
            ->dto
            ->args;
    }

    public function getAnswer(): ?string
    {
        return $this
            ->dto
            ->answer;
    }

    public function isNotAwaiting(): bool
    {
        return $this
            ->dto
            ->answerState === AnswerState::NotAwaiting;
    }

    public function isPending(): bool
    {
        return $this
            ->dto
            ->answerState === AnswerState::Pending;
    }

    public function isReceived(): bool
    {
        return $this
            ->dto
            ->answerState === AnswerState::Received;
    }

    public function getLogs(): array
    {
        return $this
            ->dto
            ->logs;
    }

    public function getSignals(): array
    {
        return $this
            ->dto
            ->signals;
    }

    public function getStatus(): bool
    {
        return $this
            ->dto
            ->status;
    }

    public function getPid(): ?int
    {
        return $this
            ->dto
            ->pid;
    }

    public function getUser(): ?Model
    {
        return $this
            ->dto
            ->user;
    }

    public function appendLog(
        string $message,
    ): void {
        $this
            ->dto
            ->logs[] = $message;

        $this
            ->cache
            ->set($this->dto);
    }

    public function setPid(
        int $pid,
    ): void {
        $this
            ->dto
            ->pid = $pid;

        $this->appendLog('PID: ' . $pid);
    }

    public function notAwaiting(): void
    {
        $this
            ->dto
            ->answerState = AnswerState::NotAwaiting;

        $this
            ->dto
            ->answer = null;

        $this
            ->cache
            ->set($this->dto);
    }

    public function pending(): void
    {
        $this
            ->dto
            ->answerState = AnswerState::Pending;

        $this->appendLog(':wait:');
    }

    public function received(
        string $answer,
    ): void {
        $this
            ->dto
            ->answerState = AnswerState::Received;

        $this
            ->dto
            ->answer = $answer;

        $this
            ->cache
            ->set($this->dto);
    }

    /**
     * The signal is sent by the runner, because only it owns the process (it can be on another server).
     */
    public function sigterm(): void
    {
        $this->requestSignal(Signals::Sigterm);
    }

    public function sigkill(): void
    {
        $this->requestSignal(Signals::Sigkill);
    }

    public function pullSignal(): ?Signals
    {
        return $this
            ->cache
            ->pullSignal($this->dto->commandId);
    }

    public function finished(): void
    {
        $this
            ->dto
            ->status = true;

        $this->appendLog('Finished');
    }

    public function stopRunIfNeeded(): void
    {
        if (!$this->dto->status) {
            return;
        }

        $this->stopRun();
    }

    public function stopRun(): void
    {
        $this
            ->cache
            ->delete($this->dto->commandId);
    }

    private function requestSignal(
        Signals $signal,
    ): void {
        $this
            ->cache
            ->setSignal($this->dto->commandId, $signal);
    }
}
