<?php

declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Traits;

use Tkachikov\Memory\Memory as MemoryHelper;
use Tkachikov\LaravelPulse\Models\CommandLog;
use Tkachikov\LaravelPulse\Models\CommandRun;
use Tkachikov\LaravelPulse\Enums\TypeMessageEnum;
use Tkachikov\LaravelPulse\Helpers\DatabaseHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkachikov\LaravelPulse\Models\Command as CommandModel;

trait PulseRunnerTrait
{
    public static int $waiting = 2;

    public static int $maxLogSize = 1000;

    private readonly MemoryHelper $memoryHelper;

    private readonly DatabaseHelper $databaseHelper;

    private readonly CommandRun $run;
    
    private array $logs = [];

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
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

        return $state;
    }

    /**
     * @param $string
     * @param $verbosity
     *
     * @return void
     */
    public function alert($string, $verbosity = null): void
    {
        parent::alert($string, $verbosity);
        $this->appendLog(TypeMessageEnum::ALERT, $string);
    }

    /**
     * @param $string
     * @param $style
     * @param $verbosity
     *
     * @return void
     */
    public function line($string, $style = null, $verbosity = null): void
    {
        parent::line($string, $style, $verbosity);
        if ($style !== TypeMessageEnum::COMMENT->value) {
            $this->appendLog(TypeMessageEnum::from($style), $string);
        }
    }

    /**
     * @return void
     */
    private function initDi(): void
    {
        $this->memoryHelper = app(MemoryHelper::class);
        $this->databaseHelper = app(DatabaseHelper::class);
    }

    /**
     * @return void
     */
    private function createRun(): void
    {
        if (
            !$this->databaseHelper->hasConnect()
            || !$this->databaseHelper->hasTable(CommandModel::class)
            || !$this->databaseHelper->hasTable(CommandRun::class)
        ) {
            return;
        }
        $this->run = CommandRun::create([
            'command_id' => CommandModel::firstWhere('class', $this::class)->id,
            'schedule_id' => null,
            'state' => self::$waiting,
        ]);
    }

    /**
     * @param int $state
     *
     * @return void
     */
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

    /**
     * @param TypeMessageEnum $type
     * @param string          $message
     *
     * @return void
     */
    private function appendLog(TypeMessageEnum $type, string $message): void
    {
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

    /**
     * @return void
     */
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
