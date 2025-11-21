<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum Signals: string
{
    case Sigkill = 'sigkill';
    case Sigterm = 'sigterm';

    public static function getSignalsState(): array
    {
        return [
            self::Sigkill->value => self::Sigkill->isEnabled(),
            self::Sigterm->value => self::Sigterm->isEnabled(),
        ];
    }

    public function getSignal(): int
    {
        return match ($this) {
            self::Sigkill => SIGKILL,
            self::Sigterm => SIGTERM,
        };
    }

    public function isEnabled(): bool
    {
        $constName = str($this->value)
            ->upper()
            ->toString();

        return $this->posixExists()
            && defined($constName);
    }

    private function posixExists(): bool
    {
        return function_exists('posix_kill');
    }
}
