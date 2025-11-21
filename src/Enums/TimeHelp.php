<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

use Exception;

enum TimeHelp: string
{
    case Minutes = 'minutes';
    case Hours = 'hours';
    case Time = 'time';
    case Months = 'months';
    case DayOfWeek = 'day_of_week';
    case DayOfMonth = 'day_of_month';
    case DayOfQuarter = 'day_of_quarter';

    /**
     * @throws Exception
     */
    public function getDictionary(): array
    {
        return match ($this) {
            self::Minutes => $this->getMinutesDictionary(),
            self::Hours => $this->getHoursDictionary(),
            self::Months => $this->getMonthDictionary(),
            self::DayOfWeek => $this->getDayOfWeekDictionary(),
            self::DayOfMonth => $this->getDayOfMonthsDictionary(),
            self::DayOfQuarter => $this->getDayOfQuarterDictionary(),
            self::Time => throw new Exception('To be implemented'),
        };
    }

    private function getMinutesDictionary(): array
    {
        $minutes = range(0, 59);

        return array_combine($minutes, $minutes);
    }

    private function getHoursDictionary(): array
    {
        $hours = range(0, 23);

        return array_combine($hours, $hours);
    }

    private function getMonthDictionary(): array
    {
        $months = range(1, 12);

        $names = array_map(
            fn ($v) => now()
                ->startOfYear()
                ->addMonths($v - 1)
                ->format('F'),
            $months,
        );

        return array_combine($months, $names);
    }

    private function getDayOfWeekDictionary(): array
    {
        $daysOfWeek = range(1, 7);

        $names = array_map(
            fn ($v) => now()
                ->startOfWeek()
                ->addDays($v - 1)
                ->format('l'),
            $daysOfWeek,
        );

        return array_combine($daysOfWeek, $names);
    }

    private function getDayOfMonthsDictionary(): array
    {
        $daysOfMonth = range(1, 31);

        $names = array_map(
            fn ($v) => now()
                ->startOfWeek()
                ->addDays($v - 1)
                ->format('jS'),
            $daysOfMonth,
        );

        sort($names, SORT_NUMERIC);

        return array_combine($daysOfMonth, $names);
    }

    private function getDayOfQuarterDictionary(): array
    {
        $daysOfQuarter = range(1, 90);

        return array_combine($daysOfQuarter, $daysOfQuarter);
    }
}
