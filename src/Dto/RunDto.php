<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Dto;

use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Enums\AnswerState;

final class RunDto
{
    public function __construct(
        public int $commandId,
        public bool $schedule,
        public ?Model $user = null,
        public array $args = [],
        public array $logs = [],
        public array $signals = [],
        public bool $status = false,
        public ?int $pid = null,
        public ?string $answer = null,
        public AnswerState $answerState = AnswerState::NotAwaiting,
    ) {}
}