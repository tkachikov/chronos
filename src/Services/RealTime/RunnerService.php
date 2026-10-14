<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services\RealTime;

use Exception;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Services\CommandService;

class RunnerService
{
    /** @var resource $process */
    private $process;

    private CommandDecorator $decorator;

    private array $pipes = [];

    private StateService $state;

    private string $lastChar = '';

    public function __construct(
        private readonly CommandService $commandService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(
        int $commandId,
    ): void {
        $this->state = StateService::make($commandId);
        $command = Command::find($commandId);
        $this->decorator = $this
            ->commandService
            ->getByClass($command->class);

        if (
            $this
                ->decorator
                ->runInManual()
        ) {
            $this->run();
        }
    }

    /**
     * @throws Throwable
     */
    private function run(): void
    {
        $this->process = $this->open();

        $status = proc_get_status($this->process);
        $pid = data_get($status, 'pid');

        $this
            ->state
            ->setPid($pid);

        $this->listen();

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
    }

    /**
     * @return resource
     * @throws Exception
     */
    private function open()
    {
        $args = $this
            ->state
            ->getArgs();

        $cli = $this
            ->decorator
            ->getCliCommandToArray($args);

        $cliToString = $this
            ->decorator
            ->getCliCommandToString($args);

        $logPath = Storage::path('chronos.log');

        $descriptions = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['file', $logPath, 'a'],
        ];

        $process = proc_open(
            $cli,
            $descriptions,
            $this->pipes,
            null,
            null,
        );

        if (!is_resource($process)) {
            throw new Exception(sprintf(
                'Command not running: %s',
                $cliToString,
            ));
        }

        $this
            ->state
            ->appendLog($cliToString);

        return $process;
    }

    private function listen(): void
    {
        $this
            ->state
            ->appendLog('Waiting messages...');

        stream_set_blocking($this->pipes[1], false);
        $buffer = '';

        while (true) {
            $status = proc_get_status($this->process);
            $finished = !data_get($status, 'running');

            $in = $this->read($finished);
            $buffer .= $in;

            $this->flush($buffer, $in === '', $finished);

            $this->sendAnswerIfNeeded();

            if ($finished) {
                $this->finish($status);

                break;
            }

            $this->sendSignalIfNeeded();

            usleep(100000);
        }

        $this
            ->state
            ->finished();
    }

    /**
     * Reads everything that is available in the pipe right now.
     * After the process has finished the rest of the pipe is read without limit.
     */
    private function read(
        bool $finished,
    ): string {
        $in = '';

        while (
            $finished
            || strlen($in) < CHRONOS_MAX_MESSAGE_LENGTH
        ) {
            $chunk = fread($this->pipes[1], CHRONOS_READ_BYTES);

            if (
                $chunk === false
                || $chunk === ''
            ) {
                break;
            }

            $in .= $chunk;
        }

        return $in;
    }

    /**
     * Sends only complete lines so that a long line is not split between messages.
     * The unfinished line is sent when the process is idle (e.g. waiting for an answer) or finished.
     */
    private function flush(
        string &$buffer,
        bool $idle,
        bool $finished,
    ): void {
        $position = strrpos($buffer, "\n");

        $length = match (true) {
            $idle, $finished => strlen($buffer),
            $position === false => 0,
            default => $position + 1,
        };

        $message = substr($buffer, 0, $length);

        if ($message === '') {
            return;
        }

        if (!mb_check_encoding($message, 'UTF-8')) {
            if (!$finished) {
                return;
            }

            $message = mb_scrub($message, 'UTF-8');
        }

        $buffer = substr($buffer, $length);

        $this
            ->state
            ->appendLog($message);

        $this->checkAwaiting($this->lastChar . $message);

        $this->lastChar = substr($message, -1);
    }

    private function sendSignalIfNeeded(): void
    {
        $signal = $this
            ->state
            ->pullSignal();

        if (!$signal) {
            return;
        }

        $this
            ->state
            ->appendLog($signal->value);

        if (!proc_terminate($this->process, $signal->getSignal())) {
            $this
                ->state
                ->appendLog('Signal not sent');
        }
    }

    private function finish(
        array $status,
    ): void {
        if (!data_get($status, 'signaled')) {
            $this
                ->state
                ->appendLog(sprintf(
                    'Process finished: %s',
                    data_get($status, 'exitcode'),
                ));

            return;
        }

        $this
            ->state
            ->appendLog(sprintf(
                'Process killed by signal: %s',
                data_get($status, 'termsig'),
            ));

        rescue(fn () => $this->markRunAsKilled());
    }

    /**
     * A killed process can't update its run itself.
     */
    private function markRunAsKilled(): void
    {
        $pid = $this
            ->state
            ->getPid();

        CommandRun::query()
            ->where('command_id', $this->state->dto->commandId)
            ->where('state', 2)
            ->where(fn ($query) => $query
                ->where('pid', $pid)
                ->orWhereNull('pid'))
            ->latest('id')
            ->first()
            ?->update(['state' => 3]);
    }

    private function checkAwaiting(
        string $in,
    ): void {
        if (!str_contains($in, "\n >")) {
            return;
        }

        if (
            !$this
                ->state
                ->isNotAwaiting()
        ) {
            return;
        }

        $this
            ->state
            ->pending();
    }

    private function sendAnswerIfNeeded(): void
    {
        $this->state = $this
            ->state
            ->refresh();

        if (
            !$this
                ->state
                ->isReceived()
        ) {
            return;
        }

        $answer = $this
            ->state
            ->getAnswer();

        $this
            ->state
            ->notAwaiting();

        fwrite(
            $this->pipes[0],
            $answer . PHP_EOL,
        );
    }
}
