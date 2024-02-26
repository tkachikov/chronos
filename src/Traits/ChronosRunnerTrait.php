<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Traits;

use Throwable;
use Tkachikov\Memory\Memory as MemoryHelper;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Enums\TypeMessageEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkachikov\Chronos\Models\Command as CommandModel;

trait ChronosRunnerTrait
{
    public static int $waiting = 2;

    public static int $maxLogSize = 1000;

    private MemoryHelper $memoryHelper;

    private DatabaseHelper $databaseHelper;

    private CommandRun $run;

    private array $logs = [];

    /**
     * @throws Throwable
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->initDi();
        $this->memoryHelper->reset();
        $this->createRun();
        $this->appendLog(TypeMessageEnum::INFO, 'Running command');
        try {
            $state = parent::run($input, $output);
        } catch (Throwable $e) {
            report($e);
            $state = self::FAILURE;
            $this->appendLog(TypeMessageEnum::ERROR, $e->getMessage());
        }
        $this->appendLog(TypeMessageEnum::INFO, 'Finished command');
        $this->saveLogs();
        $this->updateRun($state);
        if (isset($e)) {
            throw $e;
        }

        return $state;
    }

    public function alert($string, $verbosity = null): void
    {
        parent::alert($string, $verbosity);
        $this->appendLog(TypeMessageEnum::ALERT, $string);
    }

    public function line($string, $style = null, $verbosity = null): void
    {
        parent::line($string, $style, $verbosity);
        if ($style !== TypeMessageEnum::COMMENT->value) {
            $this->appendLog(TypeMessageEnum::from($style), $string);
        }
    }

    private function initDi(): void
    {
        $this->memoryHelper = app(MemoryHelper::class);
        $this->databaseHelper = app(DatabaseHelper::class);
    }

    private function createRun(): void
    {
        if (
            !$this->databaseHelper->hasConnect()
            || !$this->databaseHelper->hasTable(CommandModel::class)
            || !$this->databaseHelper->hasTable(CommandRun::class)
            || !($model = CommandModel::firstWhere('class', $this::class))
        ) {
            return;
        }
        $this->run = CommandRun::create([
            'command_id' => $model->id,
            'schedule_id' => null,
            'state' => self::$waiting,
        ]);
    }

    private function updateRun(int $state): void
    {
        if (!isset($this->run)) {
            return;
        }
        $this->run->update([
            'state' => $state,
            'memory' => $this->memoryHelper->showPeak(),
        ]);
    }

    private function appendLog(TypeMessageEnum $type, string $message): void
    {
        if (!isset($this->run)) {
            return;
        }
        $this->logs[] = [
            'command_run_id' => $this->run->id ?? null,
            'type' => $type->value,
            'message' => $message,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
        if (count($this->logs) === self::$maxLogSize) {
            $this->saveLogs();
        }
    }

    private function saveLogs(): void
    {
        if (
            $this->logs
            && $this->databaseHelper->hasConnect()
            && $this->databaseHelper->hasTable(CommandLog::class)
        ) {
            CommandLog::insert($this->logs);
            $this->logs = [];
        }
    }
}
