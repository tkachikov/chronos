<?php

declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Eloquent\Collection;
use Tkachikov\LaravelPulse\Decorators\CommandDecorator;
use Tkachikov\LaravelPulse\Models\Command as CommandModel;

class CommandService
{
    private array $commands = [];

    private array $systemCommands = [];

    private Collection $models;

    public function __construct() {
        $this->init();
    }

    /**
     * @param string|null $class
     *
     * @return CommandDecorator|array
     */
    public function get(?string $class = null): CommandDecorator|array
    {
        return $class
            ? $this->commands[$class]
            : $this->getSorted();
    }

    /**
     * @return array
     */
    public function getSorted(): array
    {
        $commands = $this->commands;
        ksort($commands);

        return $commands;
    }

    /**
     * Now support only one argument for time method
     *
     * @return array
     */
    public function getTimes(): array
    {
        return [
            'everyMinute' => ['title' => 'Every 1 minute', 'params' => false],
            'everyTwoMinutes' => ['title' => 'Every 2 minutes', 'params' => false],
            'everyThreeMinutes' => ['title' => 'Every 3 minutes', 'params' => false],
            'everyFourMinutes' => ['title' => 'Every 4 minutes', 'params' => false],
            'everyFiveMinutes' => ['title' => 'Every 5 minutes', 'params' => false],
            'everyTenMinutes' => ['title' => 'Every 10 minutes', 'params' => false],
            'everyFifteenMinutes' => ['title' => 'Every 15 minutes', 'params' => false],
            'everyThirtyMinutes' => ['title' => 'Every 30 minutes', 'params' => false],
            'hourly' => ['title' => 'Every 1 hour', 'params' => false],
            'hourlyAt' => ['title' => 'Every hour at', 'params' => true],
            // 'everyOddHour' => ['title' => 'Every odd hour', 'params' => false],
            'everyTwoHours' => ['title' => 'Every 2 hour', 'params' => false],
            'everyThreeHours' => ['title' => 'Every 3 hour', 'params' => false],
            'everyFourHours' => ['title' => 'Every 4 hour', 'params' => false],
            'everySixHours' => ['title' => 'Every 6 hour', 'params' => false],
            'daily' => ['title' => 'Daily', 'params' => false],
            'dailyAt' => ['title' => 'Daily at', 'params' => true],
            // 'twiceDaily' => ['title' => 'Twice daily', 'params' => true],
            // 'twiceDailyAt' => ['title' => 'Twice daily at', 'params' => true],
            'weekly' => ['title' => 'Weekly', 'params' => false],
            // 'weeklyOn' => ['title' => 'Weekly on', 'params' => true],
            'monthly' => ['title' => 'Monthly', 'params' => false],
            // 'monthlyOn' => ['title' => 'Monthly on', 'params' => true],
            // 'twiceMonthly' => ['title' => 'Twice monthly', 'params' => true],
            'lastDayOfMonth' => ['title' => 'Last of day month', 'params' => true],
            'quarterly' => ['title' => 'Quarterly', 'params' => false],
            // 'quarterlyOn' => ['title' => 'Quarterly on', 'params' => true],
            'yearly' => ['title' => 'Yearly', 'params' => false],
            // 'yearlyOn' => ['title' => 'Yearly on', 'params' => true],
        ];
    }

    /**
     * @return void
     */
    private function init(): void
    {
        $this->models = CommandModel::get();
        foreach (Artisan::all() as $name => $command) {
            $decorateCommand = new CommandDecorator($command);
            $hasModel = $this->models->firstWhere('class', $command::class);
            if (!$hasModel) {
                $this->models->push($decorateCommand->getModel());
            }
            $property = $decorateCommand->isSystem()
                ? 'systemCommands'
                : 'commands';
            $this->$property[$command::class] = $decorateCommand;
        }
    }
}
