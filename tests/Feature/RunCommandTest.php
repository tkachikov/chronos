<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Tkachikov\Chronos\Models\CommandLog;
use Tkachikov\Chronos\Models\CommandRun;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Tests\Feature\TestCase;

#[RunTestsInSeparateProcesses]
final class RunCommandTest extends TestCase
{
    public static function getScheduleTimes(): array
    {
        return [
            [
                'method' => 'everyMinute',
                'params' => null,
                'time' => '2038-01-19 03:01:00',
            ],
            [
                'method' => 'everyTwoMinute',
                'params' => null,
                'time' => '2038-01-19 03:02:00',
            ],
            [
                'method' => 'everyThreeMinutes',
                'params' => null,
                'time' => '2038-01-19 03:03:00',
            ],
            [
                'method' => 'everyFourMinutes',
                'params' => null,
                'time' => '2038-01-19 03:04:00',
            ],
            [
                'method' => 'everyFiveMinutes',
                'params' => null,
                'time' => '2038-01-19 03:05:00',
            ],
            [
                'method' => 'everyTenMinutes',
                'params' => null,
                'time' => '2038-01-19 03:10:00',
            ],
            [
                'method' => 'everyFifteenMinutes',
                'params' => null,
                'time' => '2038-01-19 03:15:00',
            ],
            [
                'method' => 'everyThirtyMinutes',
                'params' => null,
                'time' => '2038-01-19 03:30:00',
            ],
            [
                'method' => 'hourly',
                'params' => null,
                'time' => '2038-01-19 03:00:00',
            ],
            [
                'method' => 'hourlyAt',
                'params' => null,
                'time' => '2038-01-19 03:00:00',
            ],
            [
                'method' => 'hourlyAt',
                'params' => ['23'],
                'time' => '2038-01-19 04:23:00',
            ],
            [
                'method' => 'everyOddHour',
                'params' => null,
                'time' => '2038-01-19 03:00:00',
            ],
            [
                'method' => 'everyOddHour',
                'params' => ['13'],
                'time' => '2038-01-19 03:13:00',
            ],
            [
                'method' => 'everyTwoHours',
                'params' => null,
                'time' => '2038-01-19 04:00:00',
            ],
            [
                'method' => 'everyTwoHours',
                'params' => ['02'],
                'time' => '2038-01-19 04:02:00',
            ],
            [
                'method' => 'everyThreeHours',
                'params' => null,
                'time' => '2038-01-19 03:00:00',
            ],
            [
                'method' => 'everyThreeHours',
                'params' => ['03'],
                'time' => '2038-01-19 03:03:00',
            ],
            [
                'method' => 'everyFourHours',
                'params' => null,
                'time' => '2038-01-19 04:00:00',
            ],
            [
                'method' => 'everyFourHours',
                'params' => ['04'],
                'time' => '2038-01-19 04:04:00',
            ],
            [
                'method' => 'everySixHours',
                'params' => null,
                'time' => '2038-01-19 06:00:00',
            ],
            [
                'method' => 'everySixHours',
                'params' => ['06'],
                'time' => '2038-01-19 06:06:00',
            ],
            [
                'method' => 'daily',
                'params' => null,
                'time' => '2038-01-19 00:00:00',
            ],
            [
                'method' => 'dailyAt',
                'params' => null,
                'time' => '2038-01-19 00:00:00',
            ],
            [
                'method' => 'dailyAt',
                'params' => ['02:02'],
                'time' => '2038-01-19 02:02:00',
            ],
            [
                'method' => 'twiceDaily',
                'params' => ['03', '15'],
                'time' => '2038-01-19 03:00:00',
            ],
            [
                'method' => 'twiceDaily',
                'params' => ['03', '15'],
                'time' => '2038-01-19 15:00:00',
            ],
            [
                'method' => 'twiceDailyAt',
                'params' => ['03', '15', '03'],
                'time' => '2038-01-19 03:03:00',
            ],
            [
                'method' => 'twiceDailyAt',
                'params' => ['03', '15', '15'],
                'time' => '2038-01-19 15:15:00',
            ],
            [
                'method' => 'weekly',
                'params' => null,
                'time' => '2038-01-17 00:00:00',
            ],
            [
                'method' => 'weeklyOn',
                'params' => ['2', '03:14'],
                'time' => '2038-01-19 03:14:00',
            ],
            [
                'method' => 'monthly',
                'params' => null,
                'time' => '2038-01-01 00:00:00',
            ],
            [
                'method' => 'monthlyOn',
                'params' => ['19', '03:14'],
                'time' => '2038-01-19 03:14:00',
            ],
            [
                'method' => 'twiceMonthly',
                'params' => ['1', '19', '03:14'],
                'time' => '2038-01-01 03:14:00',
            ],
            [
                'method' => 'twiceMonthly',
                'params' => ['1', '19', '03:14'],
                'time' => '2038-01-19 03:14:00',
            ],
            [
                'method' => 'lastDayOfMonth',
                'params' => null,
                'time' => '2038-01-31 00:00:00',
            ],
            [
                'method' => 'lastDayOfMonth',
                'params' => ['23:59'],
                'time' => '2038-01-31 23:59:00',
            ],
            [
                'method' => 'quarterly',
                'params' => null,
                'time' => '2038-01-01 00:00:00',
            ],
            [
                'method' => 'quarterly',
                'params' => null,
                'time' => '2038-07-01 00:00:00',
            ],
            [
                'method' => 'quarterlyOn',
                'params' => ['19', '03:14'],
                'time' => '2038-01-19 03:14:00',
            ],
            [
                'method' => 'yearly',
                'params' => ['19', '03:14'],
                'time' => '2038-01-01 00:00:00',
            ],
            [
                'method' => 'yearlyOn',
                'params' => ['1', '19', '03:14'],
                'time' => '2038-01-19 03:14:00',
            ],
            [
                'method' => 'yearlyOn',
                'params' => ['8', '8', '08:08'],
                'time' => '2038-08-08 08:08:00',
            ],
        ];
    }

    public function testRun(): void
    {
        $this->makeCommand();

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $decorator = $manager
            ->getApps()
            ->first();

        $result = $this
            ->artisan($decorator->getClassName())
            ->run();

        $this->assertEquals(Command::SUCCESS, $result);

        $model = CommandModel::firstWhere('class', $decorator->getClassName());

        $this->assertDatabaseMissing(
            (new CommandRun())->getTable(),
            ['command_id' => $model->id],
        );
    }

    public function testRunWithChronosTrait(): void
    {
        $this->makeCommand(withChronosTrait: true);

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $decorator = $manager
            ->getApps()
            ->first();

        $this
            ->artisan($decorator->getClassName())
            ->run();

        $model = CommandModel::firstWhere('class', $decorator->getClassName());

        $this->assertDatabaseHas(
            (new CommandRun())->getTable(),
            ['command_id' => $model->id],
        );

        $run = CommandRun::firstWhere('command_id', $model->id);

        $this->assertEquals(Command::SUCCESS, $run->status);
    }

    public function testLogs(): void
    {
        $this->makeCommand(
            command: 'app:test {--uuid=}',
            withChronosTrait: true,
            body: "\$this->info(\$this->option('uuid'));",
        );

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $decorator = $manager
            ->getApps()
            ->first();

        $model = $decorator->getModel();
        $uuid = Str::uuid7()->toString();

        $this
            ->artisan('app:test', ['--uuid' => $uuid])
            ->run();

        $run = $model
            ->runs()
            ->latest('id')
            ->first();

        $this->assertDatabaseHas(
            (new CommandLog())->getTable(),
            [
                'command_run_id' => $run->id,
                'type' => 'info',
                'message' => $uuid,
            ],
        );
    }

    #[DataProvider('getScheduleTimes')]
    public function testRunFromSchedule(
        string $method,
        ?array $params,
        string $time,
    ): void {
        $this->makeCommand(
            command: 'app:test {--uuid=}',
            withChronosTrait: true,
            body: "\$this->info(\$this->option('uuid'));",
        );

        $manager = $this
            ->app
            ->make(CommandManager::class);

        $decorator = $manager
            ->getApps()
            ->first();

        $model = $decorator->getModel();
        $uuid = Str::uuid7()->toString();

        Schedule::create([
            'command_id' => $model->id,
            'args' => [['key' => '--uuid', 'value' => $uuid]],
            'time_method' => $method,
            'time_params' => $params,
            'run' => true,
        ]);

        $this->travelTo(Carbon::parse($time));

        $this
            ->app
            ->getProvider(ChronosServiceProvider::class)
            ->boot();

        $scheduler = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        $events = $scheduler->dueEvents($this->app);

        $this->assertCount(1, $events);
        $this->assertTrue(str($events->first()->command)->contains($uuid));
        $this->assertTrue($events->first()->isDue($this->app));
    }
}