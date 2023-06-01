<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Enums;

enum TypeMessageEnum: string
{
    case COMMENT = 'comment';

    case INFO = 'info';

    case ALERT = 'alert';

    case QUESTION = 'question';

    case ERROR = 'error';

    case WARNING = 'warning';

    /**
     * @return string
     */
    public function css(): string
    {
        return (match ($this) {
            self::COMMENT => CssLevelClassEnum::SECONDARY,
            self::INFO => CssLevelClassEnum::PRIMARY,
            self::QUESTION, self::ALERT => CssLevelClassEnum::WARNING,
            self::ERROR, self::WARNING => CssLevelClassEnum::DANGER,
        })->value;
    }
}
