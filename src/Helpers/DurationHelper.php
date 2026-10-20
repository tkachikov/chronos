<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Helpers;

/**
 * A duration in seconds as one unit: 45s, 8m, 2h, 3d.
 */
class DurationHelper
{
    private const UNITS = [
        'd' => 86400,
        'h' => 3600,
        'm' => 60,
        's' => 1,
    ];

    /**
     * The biggest unit, rounded to the nearest whole one.
     */
    public static function format(int|float|string|null $seconds): string
    {
        if ($seconds === null || $seconds === '') {
            return '—';
        }

        $seconds = max(0, (float) $seconds);
        $bigger = null;

        foreach (self::UNITS as $unit => $size) {
            if ($seconds >= $size || $size === 1) {
                $value = (int) round($seconds / $size);

                // Rounded up to the next unit (e.g. 59.6m): one of it
                return $bigger !== null && $value * $size >= self::UNITS[$bigger]
                    ? '1' . $bigger
                    : $value . $unit;
            }

            $bigger = $unit;
        }

        return '0s';
    }

    /**
     * A range of two durations: one of them when they are equal, the unit once when it is the same (2–8m).
     */
    public static function range(int|float|string|null $min, int|float|string|null $max): string
    {
        [$from, $to] = [self::format($min), self::format($max)];

        if ($from === $to) {
            return $from;
        }

        if (
            preg_match('/^(\d+)([a-z])$/', $from, $a)
            && preg_match('/^(\d+)([a-z])$/', $to, $b)
            && $a[2] === $b[2]
        ) {
            return "{$a[1]}–{$b[1]}{$a[2]}";
        }

        return "{$from} – {$to}";
    }
}
