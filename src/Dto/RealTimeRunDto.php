<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Tkachikov\Chronos\Enums\AnswerState;

final class RealTimeRunDto
{
    public function __construct(
        public int $userId,
        public int $commandId,
        public array $args = [],
        public array $logs = [],
        public array $signals = [],
        public bool $status = false,
        public ?int $pid = null,
        public ?string $answer = null,
        public AnswerState $answerState = AnswerState::NotAwaiting,
    ) {}
}