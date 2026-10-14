<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Console\Signals;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tkachikov\Chronos\Tests\Feature\TestCase;

#[RunTestsInSeparateProcesses]
#[RequiresPhpExtension('pcntl')]
/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires extension pcntl
 */
final class SigtermTest extends TestCase
{
    public function testSigtermFinishesCommand(): void
    {
        $this->makeCommand(
            withChronosTrait: true,
            body: 'sleep(10);',
        );

        $this->assertSame(128 + SIGTERM, $this->runAndSendSigterm());
    }

    public function testSigtermIsHandledByCommandItself(): void
    {
        $this->makeCommand(
            withChronosTrait: true,
            body: '$this->trap(SIGTERM, fn () => null); sleep(10);',
        );

        $this->assertSame(0, $this->runAndSendSigterm());
    }

    private function runAndSendSigterm(): int
    {
        Signals::resolveAvailabilityUsing(fn () => true);

        $pid = pcntl_fork();

        if ($pid === 0) {
            $code = $this
                ->artisan('app:test')
                ->run();

            exit($code);
        }

        usleep(1000000);
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);

        return pcntl_wexitstatus($status);
    }
}
