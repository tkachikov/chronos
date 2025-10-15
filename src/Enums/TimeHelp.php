<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Enums;

enum TimeHelp: string
{
    case Minutes = 'From 0 to 59';
    case Hours = 'Format 24 hours. From 0 to 23';
    case Time = 'Format 24 hours. Example: 14:55';
    case Months = '1 - January, 2 - February and etc. From 1 to 12';
    case DayOfWeek = '1 - Monday, 2 - Tuesday and etc. Example: 3';
    case DayOfMonth = 'from 1 to 28|29|30|31';
    case DayOfQuarter = 'from 1 to 90|91|92';

    public function getDictionary(): array
    {
        return match ($this) {
            self::Minutes => $this->getMinutesDictionary(),
            self::Hours => $this->getHoursDictionary(),
            self::Months => $this->getMonthDictionary(),
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
            fn($v) => now()
                ->startOfYear()
                ->addMonths($v - 1)
                ->format('F'),
            $months,
        );

        return array_combine($months, $names);
    }
}
