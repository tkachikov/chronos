<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;

final class FreeLogsTest extends TestCase
{
    public function testHoursOption(): void
    {
        $command = Command::create(['class' => 'App\\Console\\Commands\\Test']);
        // The command itself stores its runs: only the runs of the test command are counted
        $runs = fn (): int => CommandRun::where('command_id', $command->id)->count();
        $old = CommandRun::create(['command_id' => $command->id, 'state' => 0, 'created_at' => now()->subHours(30)]);
        $recent = CommandRun::create(['command_id' => $command->id, 'state' => 0, 'created_at' => now()->subHours(2)]);
        CommandLog::create(['command_run_id' => $old->id, 'type' => 'info', 'message' => 'old', 'created_at' => now()->subHours(30)]);

        $this
            ->artisan('chronos:free-logs --hours=48')
            ->assertSuccessful();
        $this->assertSame(2, $runs());

        $this
            ->artisan('chronos:free-logs')
            ->assertSuccessful();
        $this->assertNotNull($recent->fresh());
        $this->assertNull($old->fresh());
        $this->assertSame(0, CommandLog::where('command_run_id', $old->id)->count());

        $this
            ->artisan('chronos:free-logs --hours=1')
            ->assertSuccessful();
        $this->assertSame(0, $runs());
    }
}
