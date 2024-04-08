<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Jobs\CommandRunRealTimeJob;
use Tkachikov\Chronos\Models\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommandRunService
{
    /** @var resource $process */
    private $process;

    private readonly Command $command;

    private readonly CommandDecorator $decorator;

    private array $pipes = [];

    private string $uuid;

    public function __construct(
        private readonly CommandService $commandService,
    ) {
    }

    public function initRun(Command $command): string
    {
        $this->command = $command;
        $this->uuid = Str::uuid()->toString();
        cache()->set($this->getKey(), [
            'data' => [],
            'status' => false,
        ]);
        CommandRunRealTimeJob::dispatch($command, $this->uuid);

        return $this->uuid;
    }

    public function getLogs(Command $command, string $uuid): array
    {
        $this->command = $command;
        $this->uuid = $uuid;

        return cache()->get($this->getKey());
    }

    public function setAnswer(Command $command, string $uuid, string $answer): void
    {
        $this->command = $command;
        $this->uuid = $uuid;
        cache()->set($this->getKey() . '-answer', $answer);
    }

    public function run(Command $command, string $uuid): int
    {
        $this->command = $command;
        $this->uuid = $uuid;
        $this->decorator = $this->commandService->get($command->class);

        return $this->runProcess();
    }

    private function runProcess(): int
    {
        $this->process = $this->createProcess();

        $this->listen();

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);

        return proc_close($this->process);
    }

    /**
     * @return resource
     * @throws Exception
     */
    private function createProcess()
    {
        $descriptions = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['file', Storage::path('chronos.log'), 'a'],
        ];

        $process = proc_open(
            $this->getCliCommand(),
            $descriptions,
            $this->pipes,
            null,
            null,
        );

        if (!is_resource($process)) {
            throw new Exception('Command not running: ' . $this->getCliCommand());
        }

        return $process;
    }

    private function getCliCommand(): string
    {
        return 'php ' . base_path('artisan ' . $this->decorator->getSignature());
    }

    private function listen(): void
    {
        stream_set_blocking($this->pipes[1], false);

        while (!feof($this->pipes[1])) {
            $in = fread($this->pipes[1], 1024);
            if ($in) {
                $this->appendLog($in);
            }

            $read = [STDIN];
            $write = null;
            $error = null;
            if (stream_select($read, $write, $error, 0) > 0) {
                $answerKey = $this->getKey() . '-answer';
                if (!cache()->has($answerKey) && str_contains($in, "\n >")) {
                    cache()->set($answerKey, 0);
                    $this->appendLog(':wait:');
                }
                $answer = cache()->get($answerKey);
                if ($answer != 0) {
                    cache()->delete($answerKey);
                    fwrite($this->pipes[0], $answer.PHP_EOL);
                }
            }

            usleep(100000);
        }

        $this->appendLog('Finished', true);
    }

    private function getKey(): string
    {
        return "chronos-commands-{$this->command->id}-$this->uuid";
    }

    private function appendLog(string $log, bool $status = false): void
    {
        $data = cache()->get($this->getKey());
        $data['data'][] = $log;
        if ($status) {
            $data['status'] = $status;
        }
        cache()->set($this->getKey(), $data);
    }
}