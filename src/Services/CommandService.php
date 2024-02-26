<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Illuminate\Console\Command;
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
            if (!isset($this->commands[$class])) {
                throw new Exception('Command not found');
            }

            return $this->commands[$class];
        }

        return empty($this->commands)
            ? []
            : $this->getSorted();
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
