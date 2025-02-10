<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CommandService
{
    private array $commands = [];

    private array $systemCommands = [];

    public function __construct(
        private readonly DatabaseHelper $databaseHelper,
    ) {
        if ($this->databaseHelper->hasTable(CommandModel::class)) {
            $this->init();
        }
    }

    /**
     * @throws Exception
     */
    public function get(?string $class = null): CommandDecorator|array
    {
        if ($class) {
            return $this->commands[$class];
        }

        return empty($this->commands)
            ? []
            : $this->getSorted();
    }

    public function exists(string $class): bool
    {
        return isset($this->commands[$class]);
    }

    /**
     * @throws Exception
     */
    public function getSorted(?string $sortKey = null, ?string $sortBy = null): array
    {
        if ($sortBy && !in_array($sortBy, ['asc', 'desc'])) {
            throw new Exception('Not sort direction');
        }
        if (!$sortKey) {
            return collect($this->commands)
                ->sortBy(fn ($item) => $item->getDirectory())
                ->toArray();
        }
        $sortMethod = 'sortBy' . ($sortBy === 'desc' ? 'Desc' : '');

        return collect($this->commands)
            ->$sortMethod(function ($decorator) use ($sortKey, $sortBy) {
                return $decorator->getModel()->metrics->$sortKey ?? ($sortBy === 'asc' ? INF : -INF);
            })->toArray();
    }

    /**
     * @description Now support only one argument for time method
     */
    public function getTimes(): array
    {
        return [
            // 'cron' => ['title' => 'Cron', 'params' => true],

            // Seconds section
            'everySecond' => ['title' => 'Every second', 'params' => false],
            'everyTwoSeconds' => ['title' => 'Every 2 seconds', 'params' => false],
            'everyFiveSeconds' => ['title' => 'Every 5 seconds', 'params' => false],
            'everyTenSeconds' => ['title' => 'Every 10 seconds', 'params' => false],
            'everyFifteenSeconds' => ['title' => 'Every 15 seconds', 'params' => false],
            'everyTwentySeconds' => ['title' => 'Every 20 seconds', 'params' => false],
            'everyThirtySeconds' => ['title' => 'Every 30 seconds', 'params' => false],

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

    private function init(): void
    {
        $models = CommandModel::get()->keyBy('class');
        /** @var Command|SymfonyCommand $command */
        foreach (Artisan::all() as $command) {
            $decorator = new CommandDecorator($command);
            $hasModel = $models->get($command::class);
            if (!$hasModel) {
                $models->push($decorator->getModel());
            }
            if ($decorator->isSystem() && !$decorator->isChronosCommands()) {
                $this->systemCommands[$command::class] = $decorator;
            } else {
                $this->commands[$command::class] = $decorator;
            }
        }
    }
}
