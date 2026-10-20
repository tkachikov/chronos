<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Helpers;

/**
 * Arguments of a command from the forms of the panel: a value by the name of the argument or the option.
 */
class ArgsHelper
{
    /**
     * Cleans the values of the forms: "on" of a switch is true, empty values and empty items of arrays are dropped.
     *
     * @param iterable<array-key, mixed> $args
     * @return array<string, mixed>
     */
    public static function clean(iterable $args): array
    {
        $cleaned = [];

        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($item) => !self::isEmpty($item)));
            }

            if (self::isEmpty($value)) {
                continue;
            }

            $cleaned[(string) $key] = $value === 'on' ?: $value;
        }

        return $cleaned;
    }

    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === false || $value === [];
    }
}
