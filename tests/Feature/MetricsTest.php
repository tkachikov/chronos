<?php

declare(strict_types=1);

namespace Feature;

use Tkachikov\Chronos\Helpers\MemoryHelper;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandMetric;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Services\ScheduleService;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class MetricsTest extends TestCase
{
    public function testMetricsWithDifferentMemoryUnits(): void
    {
        $command = $this->command();

        $this->createRun($command, seconds: 10, memory: '512 KB');
        $this->createRun($command, seconds: 20, memory: '1.5 MB');
        $this->createRun($command, seconds: 60, memory: '2 MB', state: 2);

        $this->updateMetrics();

        $metric = CommandMetric::firstWhere('command_id', $command->id);

        $this->assertSame('15', $metric->time_avg);
        $this->assertSame('10', $metric->time_min);
        $this->assertSame('20', $metric->time_max);
        $this->assertSame('1 MB', $metric->memory_avg);
        $this->assertSame('512 KB', $metric->memory_min);
        $this->assertSame('1.5 MB', $metric->memory_max);
    }

    public function testMinAndMaxAreKeptAfterFreeLogs(): void
    {
        $command = $this->command();

        $this->createRun($command, seconds: 5, memory: '1 MB');
        $this->createRun($command, seconds: 50, memory: '8 MB');
        $this->updateMetrics();

        CommandRun::query()->delete();

        $this->createRun($command, seconds: 20, memory: '4 MB');
        $this->updateMetrics();

        $metric = CommandMetric::firstWhere('command_id', $command->id);

        $this->assertSame('20', $metric->time_avg);
        $this->assertSame('5', $metric->time_min);
        $this->assertSame('50', $metric->time_max);
        $this->assertSame('4 MB', $metric->memory_avg);
        $this->assertSame('1 MB', $metric->memory_min);
        $this->assertSame('8 MB', $metric->memory_max);
        $this->assertSame(1, CommandMetric::count());
    }

    public function testMetricsOfCommandWithoutRunsAreKept(): void
    {
        $first = $this->command('First');
        $second = $this->command('Second');

        $this->createRun($first, seconds: 5, memory: '1 MB');
        $this->createRun($second, seconds: 7, memory: '2 MB');
        $this->updateMetrics();

        CommandRun::where('command_id', $first->id)->delete();
        $this->updateMetrics();

        $this->assertSame('5', CommandMetric::firstWhere('command_id', $first->id)->time_avg);
        $this->assertSame('7', CommandMetric::firstWhere('command_id', $second->id)->time_avg);
    }

    public function testMemoryToBytes(): void
    {
        $helper = new MemoryHelper();

        $this->assertSame(512.0 * 1024, $helper->toBytes('512 KB'));
        $this->assertSame(1.5 * 1024 ** 2, $helper->toBytes('1.5 MB'));
        $this->assertSame(10.0, $helper->toBytes('10 B'));
        $this->assertNull($helper->toBytes(null));
        $this->assertNull($helper->toBytes('unknown'));
        $this->assertGreaterThan($helper->toBytes('512 KB'), $helper->toBytes('2 MB'));
    }

    private function command(string $name = 'Test'): Command
    {
        return Command::create(['class' => "App\\Console\\Commands\\$name"]);
    }

    private function createRun(
        Command $command,
        int $seconds,
        string $memory,
        int $state = 0,
    ): void {
        $start = now()->subHour();

        CommandRun::insert([
            'command_id' => $command->id,
            'state' => $state,
            'memory' => $memory,
            'created_at' => $start,
            'updated_at' => $start->copy()->addSeconds($seconds),
        ]);
    }

    private function updateMetrics(): void
    {
        $this
            ->app
            ->make(ScheduleService::class)
            ->updateMetrics();
    }
}
