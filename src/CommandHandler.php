<?php
declare(strict_types=1);

namespace Tkachikov\LaravelCommands;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\IncomingEntry;
use Tkachikov\LaravelCommands\Decorators\IncomeEntryDecorator;
use Tkachikov\LaravelCommands\Enums\TypeMessageEnum;
use Tkachikov\Memory\Memory as MemoryHelper;
use Tkachikov\LaravelCommands\Models\CommandLog;
use Tkachikov\LaravelCommands\Models\CommandRun;
use Laravel\Telescope\Contracts\EntriesRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tkachikov\LaravelCommands\Helpers\DatabaseHelper;
use Tkachikov\LaravelCommands\Models\Command as CommandModel;

class CommandHandler extends Command
{
    public const WAITING = 2;

    protected MemoryHelper $memoryHelper;

    protected DatabaseHelper $databaseHelper;

    protected CommandRun $run;

    protected CommandLog $log;

    protected array $logs = [];

    protected int $maxSizeLogs = 1000;

    protected int $maxSizeTelescope = 1000;

    protected bool $runInSchedule = true;

    protected bool $runInManual = true;

    public function __construct()
    {
        parent::__construct();
        $this->memoryHelper = app(MemoryHelper::class);
        $this->databaseHelper = app(DatabaseHelper::class);
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @return bool
     */
    public function runInSchedule(): bool
    {
        return $this->runInSchedule;
    }

    /**
     * @return bool
     */
    public function runInManual(): bool
    {
        return $this->runInManual;
    }

    /**
     * @return string
     */
    public function runInScheduleHtml(): string
    {
        return $this->getParamHtml($this->runInSchedule());
    }

    /**
     * @return string
     */
    public function runInManualHtml(): string
    {
        return $this->getParamHtml($this->runInManual());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->memoryHelper->reset();
        $this->setTelescopeHook();
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
     * @return void
     */
    protected function setTelescopeHook(): void
    {
        if (
            class_exists('Laravel\Telescope\Telescope')
            && config('telescope.enabled')
        ) {
            $batchId = Str::orderedUuid()->toString();
            Telescope::afterRecording(function (
                Telescope $telescope,
                IncomingEntry $entry,
            ) use ($batchId): void {
                $entry = $this->prepareEntries($telescope, $entry, $batchId);
                $this->updateTelescopeId($entry);
                $this->flushEntries($telescope);
            });
        }
    }

    /**
     * @param Telescope     $telescope
     * @param IncomingEntry $entry
     * @param string        $batchId
     *
     * @return IncomingEntry
     */
    protected function prepareEntries(
        Telescope $telescope,
        IncomingEntry $entry,
        string $batchId,
    ): IncomingEntry {
        foreach (['entriesQueue', 'updatesQueue'] as $key) {
            if (!count($telescope::${$key})) {
                continue;
            }
            $item = &$telescope::${$key}[count($telescope::${$key}) - 1];
            if ($item instanceof IncomeEntryDecorator) {
                continue;
            }
            $params = [
                'content' => $entry->content,
                'uuid' => $entry->uuid,
            ];
            $item = app(IncomingEntry::class, $params)
                ->entry($entry)
                ->batchId($batchId);
            if ($item->uuid === $entry->uuid) {
                $entry = $item;
            }
        }

        return $entry;
    }

    /**
     * @param IncomingEntry $entry
     *
     * @return void
     */
    protected function updateTelescopeId(IncomingEntry $entry): void
    {
        if (
            $entry->type === 'command'
            && isset($this->run)
        ) {
            $this->run->update(['telescope_id' => $entry->uuid]);
        }
    }

    /**
     * @param Telescope $telescope
     *
     * @return void
     */
    protected function flushEntries(Telescope $telescope): void
    {
        $countEntries = count($telescope::$entriesQueue) + count($telescope::$updatesQueue);
        if ($countEntries >= $this->maxSizeTelescope) {
            $telescope::store(app(EntriesRepository::class));
        }
    }

    /**
     * @return void
     */
    protected function createRun(): void
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
            'state' => self::WAITING,
        ]);
    }

    /**
     * @param int $state
     *
     * @return void
     */
    protected function updateRun(int $state): void
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
     * @param TypeMessageEnum $type
     * @param string          $message
     *
     * @return void
     */
    protected function appendLog(TypeMessageEnum $type, string $message): void
    {
        $this->logs[] = [
            'command_run_id' => $this->run->id ?? null,
            'type' => $type->value,
            'message' => $message,
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
        if (count($this->logs) === $this->maxSizeLogs) {
            $this->saveLogs();
        }
    }

    /**
     * @return void
     */
    protected function saveLogs(): void
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

    /**
     * @param bool $state
     *
     * @return string
     */
    protected function getParamHtml(bool $state): string
    {
        $class = $state ? 'success' : 'danger';
        $word = $state ? 'Yes' : 'No';

        return "<span class='text-$class'>$word</span>";
    }
}
