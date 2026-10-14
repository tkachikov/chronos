<?php

declare(strict_types=1);

namespace Feature\RealTime;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ReflectionClass;
use Tkachikov\Chronos\Actions\InitializeCacheAction;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\Signals;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Services\RealTime\CacheService;
use Tkachikov\Chronos\Services\RealTime\RunnerService;
use Tkachikov\Chronos\Services\RealTime\StateService;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class RunnerServiceTest extends TestCase
{
    private const COMMAND_ID = 1;

    public function testLongLineIsNotSplit(): void
    {
        $line = str_repeat('long text ', 20000);

        $logs = $this->listen(
            'echo "before\n", str_repeat("long text ", 20000), "\n", "after\n";',
        );

        $this->assertStringContainsString(
            "before\n" . $line . "\nafter\n",
            implode('', $logs),
        );
        $this->assertCount(
            1,
            array_filter($logs, fn (string $log) => str_contains($log, $line)),
        );
        $this->assertSame('Finished', end($logs));
    }

    public function testOutputIsNotCutWhenProcessFinishesBeforeReading(): void
    {
        $logs = $this->listen(
            'echo str_repeat("line\n", 2000), "last line\n";',
        );

        $this->assertStringContainsString(
            str_repeat("line\n", 2000) . "last line\n",
            implode('', $logs),
        );
    }

    public function testMultibyteLongLineIsNotSplit(): void
    {
        $line = str_repeat('длинный текст ', 5000);

        $logs = $this->listen(
            'echo str_repeat("длинный текст ", 5000), "\n";',
        );

        $this->assertContains($line . "\n", $logs);
    }

    public function testLinesAreSentWhileProcessIsRunning(): void
    {
        $logs = $this->listen(
            'echo "first\n"; usleep(500000); echo "second\n";',
        );

        $this->assertContains("first\n", $logs);
        $this->assertContains("second\n", $logs);
    }

    public function testQuestionWithoutNewLineIsSentAndAwaitsAnswer(): void
    {
        $logs = $this->listen(
            'echo "\n What is your name?:\n > "; usleep(500000);',
        );

        $question = array_search(' > ', $logs, true)
            ?: array_search("\n What is your name?:\n > ", $logs, true);

        $this->assertNotFalse($question);
        $this->assertSame(':wait:', $logs[$question + 1]);
    }

    public function testSigtermIsSentByRunner(): void
    {
        $start = microtime(true);

        $logs = $this->listen(
            'echo "started\n"; sleep(10);',
            Signals::Sigterm,
        );

        $this->assertLessThan(5, microtime(true) - $start);
        $this->assertContains('sigterm', $logs);
        $this->assertContains('Process killed by signal: ' . SIGTERM, $logs);
        $this->assertSame('Finished', end($logs));
    }

    #[RequiresPhpExtension('pcntl')]
    /**
     * @requires extension pcntl
     */
    public function testSigkillIsSentByRunner(): void
    {
        $logs = $this->listen(
            'pcntl_signal(SIGTERM, SIG_IGN); echo "started\n"; sleep(10);',
            Signals::Sigkill,
        );

        $this->assertContains('sigkill', $logs);
        $this->assertContains('Process killed by signal: ' . SIGKILL, $logs);
    }

    public function testKilledRunIsMarked(): void
    {
        $command = Command::create(['class' => 'App\\Console\\Commands\\Test']);

        $run = new CommandRun();
        $run->command()->associate($command);
        $run->state = 2;
        $run->save();

        $this->listen(
            'sleep(10);',
            Signals::Sigkill,
            $command->id,
        );

        $this->assertSame(3, $run->refresh()->state);
    }

    public function testSignalOfPreviousRunIsDropped(): void
    {
        $cache = $this
            ->app
            ->make(CacheService::class);

        $cache->setSignal(self::COMMAND_ID, Signals::Sigkill);

        $this
            ->app
            ->make(InitializeCacheAction::class)
            ->execute(new RunDto(commandId: self::COMMAND_ID));

        $this->assertNull(StateService::make(self::COMMAND_ID)->pullSignal());
    }

    public function testExpiredStateIsRestored(): void
    {
        $cache = $this
            ->app
            ->make(CacheService::class);

        $cache->set(new RunDto(commandId: self::COMMAND_ID, pid: 123));

        $state = StateService::make(self::COMMAND_ID);

        $cache->delete(self::COMMAND_ID);

        $this->assertSame(123, $state->refresh()->getPid());
        $this->assertSame(123, StateService::make(self::COMMAND_ID)->getPid());
    }

    private function listen(
        string $code,
        ?Signals $signal = null,
        int $commandId = self::COMMAND_ID,
    ): array {
        $cache = $this
            ->app
            ->make(CacheService::class);

        $cache->set(new RunDto(commandId: $commandId));

        if ($signal) {
            $cache->setSignal($commandId, $signal);
        }

        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', $code],
            [['pipe', 'r'], ['pipe', 'w']],
            $pipes,
        );

        $service = $this
            ->app
            ->make(RunnerService::class);

        $reflection = new ReflectionClass($service);

        $properties = [
            'process' => $process,
            'pipes' => $pipes,
            'state' => StateService::make($commandId),
        ];

        foreach ($properties as $name => $value) {
            $reflection
                ->getProperty($name)
                ->setValue($service, $value);
        }

        $method = $reflection->getMethod('listen');
        $method->setAccessible(true);
        $method->invoke($service);

        fclose($pipes[0]);
        fclose($pipes[1]);
        proc_close($process);

        return StateService::make($commandId)->getLogs();
    }
}
