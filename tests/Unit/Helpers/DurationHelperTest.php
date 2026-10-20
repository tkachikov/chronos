<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tkachikov\Chronos\Helpers\DurationHelper;

final class DurationHelperTest extends TestCase
{
    #[DataProvider('durations')]
    public function testFormat(int|float|string|null $seconds, string $expected): void
    {
        $this->assertSame($expected, DurationHelper::format($seconds));
    }

    #[DataProvider('ranges')]
    public function testRange(int|float|string|null $min, int|float|string|null $max, string $expected): void
    {
        $this->assertSame($expected, DurationHelper::range($min, $max));
    }

    public static function ranges(): array
    {
        return [
            'same unit' => [15, 25, '15–25s'],
            'same unit, minutes' => [120, 487, '2–8m'],
            'equal' => [25, 25, '25s'],
            'equal after rounding' => [487, 490, '8m'],
            'different units' => [45, 120, '45s – 2m'],
            'nothing' => [null, null, '—'],
        ];
    }

    public static function durations(): array
    {
        return [
            'nothing' => [null, '—'],
            'empty' => ['', '—'],
            'zero' => [0, '0s'],
            'seconds' => [45, '45s'],
            'rounded seconds' => ['2.6', '3s'],
            'a minute' => [60, '1m'],
            'minutes, the seconds are dropped' => [487, '8m'],
            'minutes rounded up' => [8 * 60 + 40, '9m'],
            'almost an hour becomes an hour' => [59 * 60 + 50, '1h'],
            'hours, the minutes are dropped' => [3905, '1h'],
            'hours rounded up' => [90 * 60, '2h'],
            'days' => [2 * 86400 + 3 * 3600, '2d'],
            'negative' => [-5, '0s'],
        ];
    }
}
