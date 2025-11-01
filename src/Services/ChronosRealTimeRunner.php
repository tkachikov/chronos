<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Services;

use Exception;
use Throwable;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command;
use Illuminate\Support\Facades\Storage;

class ChronosRealTimeRunner
{
    /** @var resource $process */
    private $process;

    private readonly CommandDecorator $decorator;

    private array $pipes = [];

    private RealTimeStateService $state;

    public function __construct(
        private readonly CommandService $commandService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        int $commandId,
    ): void {
        $this->state = RealTimeStateService::make($commandId);
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
        $in = '';

        while (!feof($this->pipes[1])) {
            $in .= fread($this->pipes[1], CHRONOS_READ_BYTES);

            if (
                $in
                && mb_check_encoding($in, 'UTF-8')
            ) {
                $this
                    ->state
                    ->appendLog($in);
                $in = '';
            }

            $this->checkAnswerState($in);

            $status = proc_get_status($this->process);

            if (
                !data_get($status, 'running')
                && empty($in)
            ) {
                $this
                    ->state
                    ->appendLog(sprintf(
                        'Process finished: %s',
                        data_get($status, 'exitcode'),
                    ));

                break;
            }

            usleep(100000);
        }

        $this
            ->state
            ->finished();
    }

    public function checkAnswerState(
        string $in,
    ): void {
        $read = [STDIN];
        $write = null;
        $error = null;

        $changes = stream_select(
            $read,
            $write,
            $error,
            0,
        );

        if ($changes > 0) {
            $this->checkAwaiting($in);
            $this->sendAnswerIfNeeded();
        }
    }

    private function checkAwaiting(
        string $in,
    ): void {
        if (str_contains($in, "\n >")) {
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
