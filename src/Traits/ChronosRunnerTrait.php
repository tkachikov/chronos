<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Throwable;
use Tkachikov\Chronos\Enums\TypeMessageEnum;
use Tkachikov\Chronos\Helpers\DatabaseHelper;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Services\RealTime\StateService;
use Tkachikov\Memory\Memory as MemoryHelper;

trait ChronosRunnerTrait
{
    public static int $waiting = 2;

    public static int $maxLogSize = 1000;

    private MemoryHelper $memoryHelper;

    private DatabaseHelper $databaseHelper;

    private ?CommandModel $model;

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

        $this->trap(SIGTERM, fn ($s) => $this->info('Signal received: ' . $s));

        try {
            $state = parent::run($input, $output);
        } catch (Throwable $e) {
            report($e);
            $state = self::FAILURE;
            $this->appendLog(TypeMessageEnum::ERROR, $e->getMessage());
        } finally {
            $this->appendLog(TypeMessageEnum::INFO, 'Finished command');
            $this->saveLogs();
            $this->updateRun($state);
        }

        if (isset($e)) {
            throw $e;
        }

        return $state;
    }

    public function alert($string, $verbosity = null): void
    {
        $string = $this->prepareMessage($string);
        parent::alert($string, $verbosity);
        $this->appendLog(TypeMessageEnum::ALERT, $string);
    }

    public function line($string, $style = null, $verbosity = null): void
    {
        $string = $this->prepareMessage($string);
        parent::line($string, $style, $verbosity);
        if ($style !== TypeMessageEnum::COMMENT->value) {
            $this->appendLog(TypeMessageEnum::from($style), $string);
        }
    }

    public function dump(mixed ...$vars): void
    {
        foreach ($vars as $var) {
            $cloner = new VarCloner();
            $dumper = new HtmlDumper();
            $output = '';
            $dumper->dump(
                $cloner->cloneVar($var),
                function ($line) use (&$output) {
                    $output .= "\r\n$line";
                },
            );

            dump($var);

            if (strlen($output) < CHRONOS_MAX_MESSAGE_LENGTH) {
                $this->appendLog(TypeMessageEnum::DUMP, $output);
            } else {
                $this->appendLog(TypeMessageEnum::INFO, 'Message is too long.');
            }
        }
    }

    public function dd(mixed ...$vars): never
    {
        $this->dump(...$vars);
        $this->appendLog(TypeMessageEnum::INFO, 'Finished command');
        $this->saveLogs();
        $this->updateRun(self::FAILURE);

        exit(1);
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
            || !$this->getModel()
        ) {
            return;
        }

        $command = $this->getModel();

        $state = rescue(
            fn () => StateService::make($command->id),
            null,
            false,
        );

        $this->run = new CommandRun();

        $this->run->command()->associate($command);
        $this->run->state = self::$waiting;

        if ($state instanceof StateService) {
            $this->run->user()->associate($state->getUser());
            $this->run->pid = $state->getPid();
            $this->run->args = $state->getArgs();
        }

        $this->run->save();
    }

    private function updateRun(int $state): void
    {
        if (
            !isset($this->run)
            || $this->run->state === $state
        ) {
            return;
        }

        $this->run->update([
            'state' => $state,
            'memory' => $this->memoryHelper->showPeak(),
        ]);
    }

    private function appendLog(TypeMessageEnum $type, string|int|bool|float $message): void
    {
        if (!isset($this->run)) {
            return;
        }

        if (str($message)->length() > CHRONOS_MAX_MESSAGE_LENGTH) {
            $message = str($message)
                    ->substr(0, CHRONOS_MAX_MESSAGE_LENGTH)
                    ->append('...')
                    ->toString();
        }

        $this->logs[] = [
            'command_run_id' => $this->run->id ?? null,
            'type' => $type->value,
            'message' => $message,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];

        if (count($this->logs) >= self::$maxLogSize) {
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

    private function prepareMessage(mixed $message): string|int|bool|float
    {
        return is_string($message) || is_numeric($message)
            ? $message
            : json_encode($message, JSON_PRETTY_PRINT);
    }

    private function getModel(): ?CommandModel
    {
        if (
            !$this->databaseHelper->hasTable(CommandModel::class)
        ) {
            return $this->model ??= null;
        }

        if (!isset($this->model)) {
            $this->model = CommandModel::firstWhere('class', $this::class);
        }

        return $this->model;
    }
}
