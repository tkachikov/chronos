<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use Tkachikov\Chronos\Models\Schedule;

final readonly class TimeDecorator
{
    public function __construct(
        public string $method,
        public array $params = [],
        public ?string $title = null,
        public ?string $description = null,
        public ?\Closure $getDescriptionCallable = null,
    ) {}

    public function getTitle(): string
    {
        return $this->title
            ?? $this->getTitleFromMethod();
    }

    public function getDescription(
        Schedule $schedule,
    ): string {
        return is_callable($this->getDescriptionCallable)
            ? call_user_func($this->getDescriptionCallable, $schedule)
            : $this->getDescriptionForSchedule($schedule);
    }

    private function getDescriptionForSchedule(
        Schedule $schedule,
    ): string {
        if (!$this->description) {
            return $this->getTitle();
        }

        $params = $schedule->time_params;

        if (!is_array($params)) {
            $params = [$params];
        }

        return sprintf(
            $this->description,
            ...$params,
        );
    }

    private function getTitleFromMethod(): string
    {
        return str($this->method)
            ->snake(' ')
            ->ucfirst()
            ->toString();
    }
}