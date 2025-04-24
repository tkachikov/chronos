<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\CommandManager;
use Tkachikov\Chronos\Decorators\CommandDecorator;

class CommandService
{
    public function __construct(
        private readonly CommandManager $manager,
    ) {}

    public function get(?string $sortKey = null, ?string $sortBy = null): array
    {
        return $sortKey && $sortBy
            ? $this->getWithSort($sortKey, $sortBy)
            : $this->getWithSortDefault();
    }

    /**
     * @param class-string $class
     */
    public function getByClass(string $class): CommandDecorator
    {
        return $this
            ->manager
            ->getByClass($class);
    }

    public function exists(string $class): bool
    {
        $existsInApps = $this
            ->manager
            ->getApps()
            ->has($class);

        $existsInChronos = $this
            ->manager
            ->getChronos()
            ->has($class);

        return $existsInApps || $existsInChronos;
    }

    public function getWithSort(string $sortKey, string $sortBy): array
    {
        return $this
            ->getBaseCommands()
            ->sortBy(
                callback: fn(CommandDecorator $decorator) => $decorator
                    ->getModel()
                    ->metrics
                    ->$sortKey
                    ?? ($sortBy === 'asc' ? INF : -INF),
                descending: $sortBy === 'desc',
            )
            ->toArray();
    }

    public function getWithSortDefault(): array
    {
        return $this
            ->getBaseCommands()
            ->sortBy(fn(CommandDecorator $decorator) => $decorator->getDirectory())
            ->toArray();
    }

    /**
     * @return Collection<class-string, CommandDecorator>
     */
    public function getBaseCommands(): Collection
    {
        $appCommands = $this
            ->manager
            ->getApps();
        $chronosCommands = $this
            ->manager
            ->getChronos();

        return $appCommands->merge($chronosCommands);
    }

    /**
     * @description Now support only one argument for a time method
     */
    public function getTimes(): array
    {
        return [
            'cron' => ['title' => 'Cron', 'params' => [
                [
                    'name' => 'custom cron',
                    'default' => '* * * * *',
                ],
            ]],

            // Seconds section
            'everySecond' => ['title' => 'Every second', 'params' => []],
            'everyTwoSeconds' => ['title' => 'Every 2 seconds', 'params' => []],
            'everyFiveSeconds' => ['title' => 'Every 5 seconds', 'params' => []],
            'everyTenSeconds' => ['title' => 'Every 10 seconds', 'params' => []],
            'everyFifteenSeconds' => ['title' => 'Every 15 seconds', 'params' => []],
            'everyTwentySeconds' => ['title' => 'Every 20 seconds', 'params' => []],
            'everyThirtySeconds' => ['title' => 'Every 30 seconds', 'params' => []],

            // Minutes section
            'everyMinute' => ['title' => 'Every 1 minute', 'params' => []],
            'everyTwoMinutes' => ['title' => 'Every 2 minutes', 'params' => []],
            'everyThreeMinutes' => ['title' => 'Every 3 minutes', 'params' => []],
            'everyFourMinutes' => ['title' => 'Every 4 minutes', 'params' => []],
            'everyFiveMinutes' => ['title' => 'Every 5 minutes', 'params' => []],
            'everyTenMinutes' => ['title' => 'Every 10 minutes', 'params' => []],
            'everyFifteenMinutes' => ['title' => 'Every 15 minutes', 'params' => []],
            'everyThirtyMinutes' => ['title' => 'Every 30 minutes', 'params' => []],

            // Hours section
            'hourly' => ['title' => 'Every 1 hour', 'params' => []],
            'hourlyAt' => ['title' => 'Every hour at', 'params' => [
                [
                    'name' => 'hour',
                    'default' => null,
                ],
            ]],
            'everyOddHour' => ['title' => 'Every odd hour', 'params' => [
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],
            'everyTwoHours' => ['title' => 'Every 2 hour', 'params' => [
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],
            'everyThreeHours' => ['title' => 'Every 3 hour', 'params' => [
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],
            'everyFourHours' => ['title' => 'Every 4 hour', 'params' => [
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],
            'everySixHours' => ['title' => 'Every 6 hour', 'params' => [
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],

            // Days section
            'daily' => ['title' => 'Daily', 'params' => []],
            'dailyAt' => ['title' => 'Daily at', 'params' => [
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],
            'twiceDaily' => ['title' => 'Twice daily', 'params' => [
                [
                    'name' => 'first hour',
                    'default' => null,
                ],
                [
                    'name' => 'second hour',
                    'default' => null,
                ],
            ]],
            'twiceDailyAt' => ['title' => 'Twice daily at', 'params' => [
                [
                    'name' => 'first hour',
                    'default' => null,
                ],
                [
                    'name' => 'second hour',
                    'default' => null,
                ],
                [
                    'name' => 'minutes',
                    'default' => null,
                ],
            ]],

            // Weeks section
            'weekly' => ['title' => 'Weekly', 'params' => []],
            'weeklyOn' => ['title' => 'Weekly on', 'params' => [
                [
                    'name' => 'day of week',
                    'default' => null,
                ],
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],

            // Months section
            'monthly' => ['title' => 'Monthly', 'params' => []],
            'monthlyOn' => ['title' => 'Monthly on', 'params' => [
                [
                    'name' => 'day of month',
                    'default' => null,
                ],
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],
            'twiceMonthly' => ['title' => 'Twice monthly', 'params' => [
                [
                    'name' => 'first day of month',
                    'default' => null,
                ],
                [
                    'name' => 'second day of month',
                    'default' => null,
                ],
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],
            'lastDayOfMonth' => ['title' => 'Last of day month', 'params' => [
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],

            // Quarters section
            'quarterly' => ['title' => 'Quarterly', 'params' => []],
            'quarterlyOn' => ['title' => 'Quarterly on', 'params' => [
                [
                    'name' => 'day of quarter',
                    'default' => null,
                ],
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],

            // Years section
            'yearly' => ['title' => 'Yearly', 'params' => []],
            'yearlyOn' => ['title' => 'Yearly on', 'params' => [
                [
                    'name' => 'month',
                    'default' => null,
                ],
                [
                    'name' => 'day',
                    'default' => null,
                ],
                [
                    'name' => 'time',
                    'default' => null,
                ],
            ]],
        ];
    }
}
