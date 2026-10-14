<?php

declare(strict_types=1);

namespace Feature\RealTime;

use ReflectionClass;
use Tkachikov\Chronos\Dto\RunDto;
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

    private function listen(
        string $code,
    ): array {
        $this
            ->app
            ->make(CacheService::class)
            ->set(new RunDto(commandId: self::COMMAND_ID));

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
            'state' => StateService::make(self::COMMAND_ID),
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

        return StateService::make(self::COMMAND_ID)->getLogs();
    }
}
