<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChronosRealTimeRunner
{
    /** @var resource $process */
    private $process;

    private readonly Command $command;

    private readonly CommandDecorator $decorator;

    private array $pipes = [];

    private string $uuid;

    private array $args;

    public function __construct(
        private readonly CommandService $commandService,
    ) {}

    public function initRun(
        Command $command,
        array $args,
        ?Model $user = null,
    ): string {
        $this->command = $command;
        $this->args = $args;
        $this->uuid = Str::uuid()->toString();

        if (cache()->has($this->getKeyInRealTime())) {
            throw new Exception(message: 'Already run');
        }

        cache()->set($this->getKeyInRealTime(), 1, now()->addMinutes(5));

        cache()->set($this->getKey(), [
            'data' => [],
            'status' => false,
        ]);
        cache()->set($this->uuid, [
            'command_id' => $this->command->id,
            'args' => $this->args,
            'user' => $user,
        ]);
        $command = base_path('artisan chronos:run-background');
        \exec("php $command $this->uuid > /dev/null 2>&1 &");

        return $this->uuid;
    }

    public function getLogs(Command $command, string $uuid): array
    {
        $this->command = $command;
        $this->uuid = $uuid;

        return cache()->get($this->getKey()) ?? [];
    }

    public function setAnswer(Command $command, string $uuid, string $answer): void
    {
        $this->command = $command;
        $this->uuid = $uuid;
        cache()->set($this->getKey() . '-answer', $answer);
    }

    public function run(
        string $uuid,
    ): int {
        $data = cache()->get($uuid);
        $this->command = Command::find($data['command_id']);
        $this->args = $data['args'];
        $this->uuid = $uuid;
        $this->decorator = $this->commandService->getByClass($this->command->class);

        if (!$this->decorator->runInManual()) {
            return 1;
        }

        return $this->runProcess();
    }

    public function sigterm(Command $command, string $uuid): void
    {
        $this->command = $command;
        $this->uuid = $uuid;

        $this->appendLog('SIGTERM');

        $this->sendSignal(SIGTERM);
    }

    public function sigkill(Command $command, string $uuid): void
    {
        $this->command = $command;
        $this->uuid = $uuid;

        $this->appendLog('SIGKILL');

        $this->sendSignal(SIGKILL);

        $this
            ->command
            ->lastRun
            ->update(['state' => 3]);
    }

    private function runProcess(): int
    {
        $this->appendLog('UUID: ' . $this->uuid);
        $this->appendLog(implode(' ', $this->getCliCommandToArray()));

        $this->process = $this->createProcess();

        $pid = proc_get_status($this->process)['pid'];
        $this->appendLog('PID: ' . $pid);
        cache()->set($pid, $this->uuid);

        $this->appendLog('Process created');

        $this->listen();

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);

        $status = proc_close($this->process);

        cache()->delete($this->getKeyInRealTime());

        return $status;
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
            $this->getCliCommandToArray(),
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

    private function getCliCommandToArray(): array
    {
        return [
            'php',
            base_path('artisan'),
            $this
                ->decorator
                ->getName(),
            ...$this
                ->decorator
                ->getArgumentsForExecToArray($this->args),
        ];
    }

    private function getCliCommand(): string
    {
        return 'php ' . base_path('artisan ' . $this->decorator->getNameWithArguments($this->args));
    }

    private function listen(): void
    {
        $this->appendLog('Waiting messages...');

        stream_set_blocking($this->pipes[1], false);
        $in = '';

        while (!feof($this->pipes[1])) {
            $in .= fread($this->pipes[1], CHRONOS_READ_BYTES);

            if (
                $in
                && mb_check_encoding($in, 'UTF-8')
            ) {
                $this->appendLog($in);
                $in = '';
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

            $status = proc_get_status($this->process);

            if (
                !data_get($status, 'running')
                && empty($in)
            ) {
                $this->appendLog('Process finished: ' . data_get($status, 'exitcode'));

                break;
            }

            usleep(100000);
        }

        $this->appendLog('Finished', true);
    }

    private function getKey(): string
    {
        return "chronos-commands-{$this->command->id}-$this->uuid";
    }

    private function getKeyInRealTime(): string
    {
        return 'chronos-commands-' . $this->command->id;
    }

    private function appendLog(string $log, bool $status = false): void
    {
        $data = cache()->get($this->getKey());
        $data['data'][] = $log;

        $hasPosixKill = function_exists('posix_kill');
        $data['signals'] = [
            'sigterm' => $hasPosixKill && defined('SIGTERM'),
            'sigkill' => $hasPosixKill && defined('SIGKILL'),
        ];

        if ($status) {
            $data['status'] = $status;
        }

        cache()->set($this->getKey(), $data);
    }

    private function sendSignal(int $signal): void
    {
        if (!function_exists('posix_kill')) {
            $this->appendLog('POSIX not installed');

            return;
        }

        $pid = cache()->get('chronos-commands-pid-' . $this->command->id);

        if ($pid) {
            \exec('kill -' . $signal . ' ' . $pid);
        }
    }
}
