<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Enums\AnswerState;

final class RunDto
{
    public function __construct(
        public int $commandId,
        public ?Model $user = null,
        public array $args = [],
        public array $logs = [],
        public array $signals = [],
        public bool $status = false,
        public ?int $pid = null,
        public ?string $answer = null,
        public AnswerState $answerState = AnswerState::NotAwaiting,
        public ?string $userType = null,
        public int|string|null $userId = null,
    ) {
        if ($user) {
            $this->userType ??= $user->getMorphClass();
            $this->userId ??= $user->getKey();
        }
    }

    /**
     * The state is stored in the cache as an array, because the cache may not unserialize
     * objects (`cache.serializable_classes` is false in Laravel 13 apps).
     */
    public function toArray(): array
    {
        return [
            'command_id' => $this->commandId,
            'user_type' => $this->userType,
            'user_id' => $this->userId,
            'args' => $this->args,
            'logs' => $this->logs,
            'signals' => $this->signals,
            'status' => $this->status,
            'pid' => $this->pid,
            'answer' => $this->answer,
            'answer_state' => $this->answerState->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            commandId: (int) $data['command_id'],
            args: (array) ($data['args'] ?? []),
            logs: (array) ($data['logs'] ?? []),
            signals: (array) ($data['signals'] ?? []),
            status: (bool) ($data['status'] ?? false),
            pid: isset($data['pid']) ? (int) $data['pid'] : null,
            answer: isset($data['answer']) ? (string) $data['answer'] : null,
            answerState: AnswerState::tryFrom((string) ($data['answer_state'] ?? '')) ?? AnswerState::NotAwaiting,
            userType: isset($data['user_type']) ? (string) $data['user_type'] : null,
            userId: $data['user_id'] ?? null,
        );
    }
}
