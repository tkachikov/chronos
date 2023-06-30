<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Services;

use Throwable;
use Exception;
use ReflectionException;
use Illuminate\Console\Parser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Finder\SplFileInfo;
use Tkachikov\LaravelPulse\CommandHandler;
use Tkachikov\LaravelPulse\Models\Schedule;
use Tkachikov\LaravelPulse\Models\CommandLog;
use Tkachikov\LaravelPulse\Models\CommandRun;
use Tkachikov\LaravelPulse\Helpers\ClassHelper;
use Tkachikov\LaravelPulse\Models\CommandMetric;
use Symfony\Component\Console\Input\InputOption;
use Tkachikov\LaravelPulse\Helpers\DatabaseHelper;
use Tkachikov\LaravelPulse\Models\Command as CommandModel;
use Tkachikov\LaravelPulse\Repositories\ScheduleRepository;
use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;

class ScheduleService
{
    private string $path = 'Console/Commands';

    private array $commands;

    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly ClassHelper        $classHelper,
        private readonly DatabaseHelper     $databaseHelper,
    ) {
    }

    /**
     * @param ScheduleConsole $scheduleConsole
     *
     * @return void
     */
    public function schedule(ScheduleConsole $scheduleConsole): void
    {
        foreach ($this->scheduleRepository->get() as $schedule) {
            $object = app($schedule->command->class);
            if (method_exists($object, 'runInSchedule') && !$object->runInSchedule()) {
                continue;
            }
            $event = $scheduleConsole
                ->command($schedule->command->class, $schedule->preparedArgs)
                ->{$schedule->time_method}(...([$schedule->time_params] ?? []));
            $properties = [
                'without_overlapping' => [$schedule->without_overlapping_time],
                'run_in_background' => [],
            ];
            foreach ($properties as $property => $params) {
                if ($schedule->$property) {
                    $method = str($property)->camel()->toString();
                    $event->$method(...$params);
                }
            }
        }
    }

    /**
     * @param string $sortKey
     * @param string $sortBy
     *
     * @throws Exception
     *
     * @return array
     */
    public function getSorted(string $sortKey, string $sortBy = 'asc'): array
    {
        $sortBy = str($sortBy)->ucfirst()->toString();
        if (!in_array($sortBy, ['Asc', 'Desc'])) {
            throw new Exception('Not found method for sorting');
        }
        $sortMethod = 'sortBy' . ($sortBy === 'Desc' ? $sortBy : '');

        return collect($this->getCommands())
            ->{$sortMethod}(fn ($command) => $command['model']->metrics->$sortKey ?? ($sortBy === 'Asc' ? INF : -INF))
            ->values()
            ->toArray();
    }

    /**
     * @param array $command
     *
     * @return array
     */
    public function getArgs(array $command): array
    {
        $args = [];
        foreach (['arguments', 'options'] as $type) {
            foreach ($command['signature'][$type] ?? [] as $arg) {
                $prefix = '';
                $value = $arg->getDefault();
                if ($arg instanceof InputOption) {
                    $prefix = '--';
                    $value = $value ?? false;
                }
                $args[$prefix.$arg->getName()] = $value;
            }
        }

        return $args;
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
     * @param array  $input
     *
     * @return Schedule
     */
    public function saveSchedule(array $input): Schedule
    {
        return $this->scheduleRepository->save($input);
    }

    /**
     * @param string $class
     * @param string $message
     *
     * @return void
     */
    public function updateWaitingRun(string $class, string $message): void
    {
        $run = CommandRun::query()
            ->whereCommand($class)
            ->whereState(CommandHandler::WAITING)
            ->first();
        if (!$run) {
            return;
        }
        $run->update(['state' => CommandHandler::FAILURE]);
        CommandLog::create([
            'command_run_id' => $run->id,
            'type' => 'error',
            'message' => $message,
        ]);
    }

    /**
     * @param int $hours
     *
     * @return array
     */
    public function freeLogs(int $hours = 24): array
    {
        $logs = [];
        $date = now()->subHours($hours);
        $classes = [
            CommandRun::class,
            CommandLog::class,
        ];
        /** @var CommandRun|CommandLog $class */
        foreach ($classes as $class) {
            $query = $class::where('created_at', '<', $date);
            $basename = str($class)->classBasename()->toString();
            $logs[] = "Free $basename: {$query->count()}";
            $query->delete();
        }

        return $logs;
    }

    /**
     * @return void
     */
    public function updateMetrics(): void
    {
        $metrics = CommandMetric::query()
            ->get()
            ->keyBy('command_id')
            ->toArray();
        $newMetrics = $this->getStatistics();
        $rows = [];
        $now = now()->format('Y-m-d H:i:s');
        foreach ($newMetrics as $commandId => $values) {
            $row = ['command_id' => $commandId, 'created_at' => $now, 'updated_at' => $now];
            foreach (['time', 'memory'] as $type) {
                foreach (['avg', 'min', 'max'] as $key) {
                    $index = $type.'_'.$key;
                    $value = (float) $values[$index];
                    $oldValue = (float) ($metrics[$index] ?? 0);
                    $row[$index] = match ($key) {
                        'avg' => ($oldValue + $value) / ($oldValue ? 2 : 1),
                        'min' => $oldValue ? min($oldValue, $value) : $value,
                        'max' => $oldValue ? max($oldValue, $value) : $value,
                    };
                    if ($type === 'memory') {
                        $row[$index] .= ' MB';
                    }
                }
            }
            $rows[] = $row;
        }
        if ($rows) {
            CommandMetric::truncate();
            CommandMetric::insert($rows);
        }
    }

    /**
     * Values memory in Megabytes
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $diffDate = $this->databaseHelper->getTimeDiffInSeconds('created_at', 'updated_at');
        $concat = $this->databaseHelper->getConcat(DB::raw('round(avg(memory))'), ' MB');
        $select = [
            'command_id',
            DB::raw("round(avg($diffDate)) time_avg"),
            DB::raw("min($diffDate) time_min"),
            DB::raw("max($diffDate) time_max"),
            DB::raw("$concat memory_avg"),
            DB::raw('min(memory) memory_min'),
            DB::raw('max(memory) memory_max'),
        ];

        return CommandRun::query()
            ->select($select)
            ->groupBy('command_id')
            ->get()
            ->keyBy('command_id')
            ->toArray();
    }

    /**
     * @param string $search
     * @param string $key
     *
     * @throws Exception
     *
     * @return array
     */
    protected function getFor(string $search, string $key): array
    {
        foreach ($this->getCommands() as $command) {
            if ($command[$key] === $search) {
                return $command;
            }
        }

        throw new Exception('Command not found');
    }
}
