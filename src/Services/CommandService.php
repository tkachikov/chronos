<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Dto\FilterDto;
use Tkachikov\Chronos\Dto\SortDto;
use Tkachikov\Chronos\Enums\LastRunStateFilterEnum;
use Tkachikov\Chronos\Enums\RunsInFilterEnum;
use Tkachikov\Chronos\Enums\SchedulersFilterEnum;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Managers\CommandRunManagerInterface;
use Tkachikov\Chronos\Models\CommandMetric;
use Tkachikov\Chronos\Models\Schedule;

class CommandService
{
    public function __construct(
        private readonly CommandManager $manager,
        private readonly CommandRunManagerInterface $commandRunManager,
    ) {}

    public function get(
        SortDto|string|null $sort = null,
        FilterDto|string|null $filter = null,
    ): array {
        if (
            is_string($sort)
            || is_null($sort)
        ) {
            $sort = new SortDto(
                column: $sort,
                direction: is_string($filter) || is_null($filter)
                    ? $filter
                    : null,
            );
        }

        $filter ??= new FilterDto();

        $lastRuns = $this
            ->commandRunManager
            ->getLastRunForEachCommand();

        return $this
            ->getBaseCommands()
            ->transform(function (CommandDecorator $decorator) use ($lastRuns) {
                $decorator
                    ->getModel()
                    ->setRelation(
                        'lastRun',
                        $lastRuns->get($decorator->getModel()->id),
                    );

                return $decorator;
            })
            ->when(
                value: in_array($sort->column, CommandMetric::$sortKeys, true),

                callback: fn(Collection $commands): Collection => $commands
                    ->sortBy(
                        callback: fn(CommandDecorator $decorator) => $decorator
                            ->getModel()
                            ->metrics
                            ->{$sort->column}
                            ?? ($sort->direction === 'asc' ? INF : -INF),

                        descending: $sort->direction === 'desc',
                    ),

                default: fn(Collection $commands): Collection => $commands
                    ->sortBy(
                        callback: fn(CommandDecorator $decorator) =>
                            $decorator->getGroupName()
                            ?? $decorator->getDirectory(),
                    ),
            )
            ->filter(
                function (
                    CommandDecorator $decorator,
                ) use (
                    $filter,
                ): bool {
                    $isValid = true;

                    if ($filter->search) {
                        $isValid = str($decorator->getFullName())->contains($filter->search, true)
                            || str($decorator->getSignature())->contains($filter->search, true)
                            || str($decorator->getDescription())->contains($filter->search, true);
                    }

                    if ($filter->runsIn) {
                        $isValid = $isValid && match ($filter->runsIn) {
                            RunsInFilterEnum::MANUAL_ON => $decorator->runInManual(),
                            RunsInFilterEnum::MANUAL_OFF => $decorator->notRunInManual(),
                            RunsInFilterEnum::SCHEDULE_ON => $decorator->runInSchedule(),
                            RunsInFilterEnum::SCHEDULE_OFF => $decorator->notRunInSchedule(),
                            default => true,
                        };
                    }

                    $schedules = $decorator
                        ->getModel()
                        ->schedules;

                    if ($filter->scheduleMethod) {
                        $isValid = $isValid
                            && in_array(
                                $filter->scheduleMethod,
                                $schedules
                                    ->pluck('time_method')
                                    ->toArray(),
                            );
                    }

                    if ($filter->schedulers) {
                        $isValid = $isValid
                            && match ($filter->schedulers) {
                                SchedulersFilterEnum::MISSING => $schedules->count() === 0,
                                SchedulersFilterEnum::HAS_ONE => $schedules->count() === 1,
                                SchedulersFilterEnum::HAS_MANY => $schedules->count() > 1,
                                SchedulersFilterEnum::SOME_OFF => $schedules
                                    ->filter(fn(Schedule $schedule) => !$schedule->run)
                                    ->count() > 0,
                                SchedulersFilterEnum::ALL_OFF => $schedules
                                    ->filter(fn(Schedule $schedule) => !$schedule->run)
                                    ->count() === $schedules->count() && $schedules->count() > 0,
                                default => true,
                            };
                    }

                    if ($filter->lastRunState) {
                        $lastRun = $decorator
                            ->getModel()
                            ->lastRun;

                        $isValid = $isValid
                            && match ($filter->lastRunState) {
                                LastRunStateFilterEnum::NEVER_RUN => !$lastRun,
                                LastRunStateFilterEnum::SUCCESS => $lastRun?->state === 0,
                                LastRunStateFilterEnum::FAILED => $lastRun?->state === 1,
                                LastRunStateFilterEnum::RUNNING => $lastRun?->state === 2,
                                default => true,
                            };
                    }

                    return $isValid;
                }
            )
            ->toArray();
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
