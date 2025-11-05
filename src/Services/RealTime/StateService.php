<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services\RealTime;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\AnswerState;
use Tkachikov\Chronos\Enums\Signals;

final readonly class StateService
{
    public function __construct(
        public RunDto $dto,
        private CacheService $cache,
    ) {}

    public static function make(
        int $commandId,
    ): self {
        $cache = app(CacheService::class);
        $dto = $cache->get($commandId);

        return new self($dto, $cache);
    }

    public function refresh(): self
    {
        $commandId = $this
            ->dto
            ->commandId;

        return self::make($commandId);
    }

    public function getArgs(): array
    {
        return $this
            ->dto
            ->args;
    }

    public function getAnswer(): string
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

    public function sigterm(): void
    {
        $this->sendSignal(Signals::Sigterm);
    }

    public function sigkill(): void
    {
        $this->sendSignal(Signals::Sigkill);
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

        $this
            ->cache
            ->delete($this->dto->commandId);
    }

    private function sendSignal(
        Signals $signal,
    ): void {
        $this->appendLog($signal->value);

        if (!function_exists('posix_kill')) {
            $this->appendLog('POSIX not installed');

            return;
        }

        $pid = $this
            ->dto
            ->pid;

        if (!$pid) {
            $this->appendLog('PID not found');

            return;
        }

        \exec(sprintf(
            'kill -%d %d',
            $signal->getSignal(),
            $pid,
        ));

        $this->finished();
    }
}