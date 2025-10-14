<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Illuminate\Support\Collection;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Decorators\ParamDecorator;
use Tkachikov\Chronos\Decorators\TimeDecorator;
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

    /**
     * @return array<int, TimeDecorator>
     */
    public function getTimes(): array
    {
        $times = [
            new TimeDecorator(
                method: 'cron',
                params: [
                    new ParamDecorator(
                        title: 'custom cron',
                        default: '* * * * *',
                    ),
                ],
            ),
            // Seconds section
            new TimeDecorator(
                method: 'everySecond',
            ),
            new TimeDecorator(
                method: 'everyTwoSeconds',
                title: 'Every 2 seconds',
            ),
            new TimeDecorator(
                method: 'everyFiveSeconds',
                title: 'Every 5 seconds',
            ),
            new TimeDecorator(
                method: 'everyTenSeconds',
                title: 'Every 10 seconds',
            ),
            new TimeDecorator(
                method: 'everyFifteenSeconds',
                title: 'Every 15 seconds',
            ),
            new TimeDecorator(
                method: 'everyTwentySeconds',
                title: 'Every 20 seconds',
            ),
            new TimeDecorator(
                method: 'everyThirtySeconds',
                title: 'Every 30 seconds',
            ),
            // Minutes section
            new TimeDecorator(
                method: 'everyMinute',
                title: 'Every 1 minute',
            ),
            new TimeDecorator(
                method: 'everyTwoMinutes',
                title: 'Every 2 minutes',
            ),
            new TimeDecorator(
                method: 'everyThreeMinutes',
                title: 'Every 3 minutes',
            ),
            new TimeDecorator(
                method: 'everyFourMinutes',
                title: 'Every 4 minutes',
            ),
            new TimeDecorator(
                method: 'everyFiveMinutes',
                title: 'Every 5 minutes',
            ),
            new TimeDecorator(
                method: 'everyTenMinutes',
                title: 'Every 10 minutes',
            ),
            new TimeDecorator(
                method: 'everyFifteenMinutes',
                title: 'Every 15 minutes',
            ),
            new TimeDecorator(
                method: 'everyThirtyMinutes',
                title: 'Every 30 minutes',
            ),
            // Hours section
            new TimeDecorator(
                method: 'hourly',
                title: 'Every 1 hour',
            ),
            new TimeDecorator(
                method: 'hourlyAt',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Every hour at',
                description: 'At %s minutes past every hour',
            ),
            new TimeDecorator(
                method: 'everyOddHour',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                description: 'At %s minutes past every odd hour',
            ),
            new TimeDecorator(
                method: 'everyTwoHours',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Every 2 hours',
                description: 'At %s minutes past every 2 hours',
            ),
            new TimeDecorator(
                method: 'everyThreeHours',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Every 3 hours',
                description: 'At %s minutes past every 3 hours',
            ),
            new TimeDecorator(
                method: 'everyFourHours',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Every 4 hours',
                description: 'At %s minutes past every 4 hours',
            ),
            new TimeDecorator(
                method: 'everySixHours',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Every 6 hours',
                description: 'At %s minutes past every 6 hours',
            ),
            // Days section
            new TimeDecorator(
                method: 'daily',
                title: 'Every day',
            ),
            // Days section
            new TimeDecorator(
                method: 'dailyAt',
                params: [
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Every day at',
                description: 'Every day at %s',
            ),
            new TimeDecorator(
                method: 'twiceDaily',
                params: [
                    new ParamDecorator(
                        title: 'first hour',
                    ),
                    new ParamDecorator(
                        title: 'second hour',
                    ),
                ],
                title: 'Twice a day',
                description: 'Every day at %s:00 and %s:00',
            ),
            new TimeDecorator(
                method: 'twiceDailyAt',
                params: [
                    new ParamDecorator(
                        title: 'first hour',
                    ),
                    new ParamDecorator(
                        title: 'second hour',
                    ),
                    new ParamDecorator(
                        title: 'minutes',
                    ),
                ],
                title: 'Twice a day at',
                getDescriptionCallable: fn(Schedule $schedule) => sprintf(
                    'Every day at %s:%s and %s:%s',
                    $schedule->time_params[0],
                    $schedule->time_params[2],
                    $schedule->time_params[1],
                    $schedule->time_params[2],
                ),
            ),
            // Weeks section
            new TimeDecorator(
                method: 'weekly',
                title: 'Every week',
            ),
            new TimeDecorator(
                method: 'weeklyOn',
                params: [
                    new ParamDecorator(
                        title: 'day of week',
                    ),
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Every week on',
                getDescriptionCallable: fn (Schedule $schedule) => sprintf(
                    'Every %s at %s',
                    now()
                        ->startOfWeek()
                        ->addDays($schedule->time_params[0] - 1)
                        ->format('l'),
                    $schedule->time_params[1],
                ),
            ),
            // Months section
            new TimeDecorator(
                method: 'monthly',
                title: 'Every month',
            ),
            new TimeDecorator(
                method: 'monthlyOn',
                params: [
                    new ParamDecorator(
                        title: 'day of month',
                    ),
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Every month on',
                getDescriptionCallable: fn (Schedule $schedule) => sprintf(
                    'On the %s day of every month at %s',
                    now()
                        ->startOfMonth()
                        ->addDays($schedule->time_params[0] - 1)
                        ->format('jS'),
                    $schedule->time_params[1],
                ),
            ),
            new TimeDecorator(
                method: 'twiceMonthly',
                params: [
                    new ParamDecorator(
                        title: 'first day of month',
                    ),
                    new ParamDecorator(
                        title: 'second day of month',
                    ),
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Every month a twice',
                getDescriptionCallable: fn (Schedule $schedule) => sprintf(
                    'On the %s and %s days of every month at %s',
                    now()
                        ->startOfMonth()
                        ->addDays($schedule->time_params[0] - 1)
                        ->format('jS'),
                    now()
                        ->startOfMonth()
                        ->addDays($schedule->time_params[1] - 1)
                        ->format('jS'),
                    $schedule->time_params[2],
                ),
            ),
            new TimeDecorator(
                method: 'lastDayOfMonth',
                params: [
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Last of day month',
                description: 'On the last day of the month at %s',
            ),
            // Quarters section
            new TimeDecorator(
                method: 'quarterly',
                title: 'Every quarter',
            ),
            new TimeDecorator(
                method: 'quarterlyOn',
                params: [
                    new ParamDecorator(
                        title: 'day of quarter',
                    ),
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Quarterly on',
                getDescriptionCallable: fn (Schedule $schedule) => sprintf(
                    'On the %s day of every quarter at %s',
                    now()
                        ->startOfMonth()
                        ->addDays($schedule->time_params[0] - 1)
                        ->format('jS'),
                    $schedule->time_params[1],
                ),
            ),
            // Years section
            new TimeDecorator(
                method: 'yearly',
                title: 'Every year',
            ),
            new TimeDecorator(
                method: 'yearlyOn',
                params: [
                    new ParamDecorator(
                        title: 'month',
                    ),
                    new ParamDecorator(
                        title: 'day',
                    ),
                    new ParamDecorator(
                        title: 'time',
                    ),
                ],
                title: 'Yearly on',
                getDescriptionCallable: fn (Schedule $schedule) => sprintf(
                    'On the %s day of %s at %s',
                    now()
                        ->startOfMonth()
                        ->addDays($schedule->time_params[1] - 1)
                        ->format('jS'),
                    now()
                        ->startOfYear()
                        ->addMonths( $schedule->time_params[0] - 1)
                        ->translatedFormat('F'),
                    $schedule->time_params[2],
                ),
            ),
        ];

        return array_combine(
            array_map(
                fn(TimeDecorator $time) => $time->method,
                $times,
            ),
            $times,
        );
    }
}
