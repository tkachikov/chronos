<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Repositories;

use Tkachikov\Chronos\Decorators\ParamDecorator;
use Tkachikov\Chronos\Decorators\TimeDecorator;
use Tkachikov\Chronos\Enums\TimeHelp;
use Tkachikov\Chronos\Models\Schedule;

final readonly class TimeRepository implements TimeRepositoryInterface
{
    public function get(): array
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
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::Minutes,
                    ),
                ],
                description: 'At %s minutes past every odd hour',
            ),
            new TimeDecorator(
                method: 'everyTwoHours',
                params: [
                    new ParamDecorator(
                        title: 'minutes',
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::Time,
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
                        help: TimeHelp::Hours,
                    ),
                    new ParamDecorator(
                        title: 'second hour',
                        help: TimeHelp::Hours,
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
                        help: TimeHelp::Hours,
                    ),
                    new ParamDecorator(
                        title: 'second hour',
                        help: TimeHelp::Hours,
                    ),
                    new ParamDecorator(
                        title: 'minutes',
                        help: TimeHelp::Minutes,
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
                        help: TimeHelp::DayOfWeek,
                    ),
                    new ParamDecorator(
                        title: 'time',
                        help: TimeHelp::Time,
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
                        help: TimeHelp::DayOfMonth,
                    ),
                    new ParamDecorator(
                        title: 'time',
                        help: TimeHelp::Time,
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
                        help: TimeHelp::DayOfMonth,
                    ),
                    new ParamDecorator(
                        title: 'second day of month',
                        help: TimeHelp::DayOfMonth,
                    ),
                    new ParamDecorator(
                        title: 'time',
                        help: TimeHelp::Time,
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
                        help: TimeHelp::Time,
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
                        help: TimeHelp::DayOfQuarter,
                    ),
                    new ParamDecorator(
                        title: 'time',
                        help: TimeHelp::Time,
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
                        help: TimeHelp::Months,
                    ),
                    new ParamDecorator(
                        title: 'day',
                        help: TimeHelp::DayOfMonth,
                    ),
                    new ParamDecorator(
                        title: 'time',
                        help: TimeHelp::Time,
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