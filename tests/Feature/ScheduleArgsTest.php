<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule as ScheduleConsole;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Bus;
use ReflectionProperty;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Jobs\CommandRunJob;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Services\ScheduleService;

/**
 * Arguments and options of every kind from the forms: saved, scheduled and run as in the console.
 */
final class ScheduleArgsTest extends TestCase
{
    private const SIGNATURE = 'app:args {arg} {list*} {--flag} {--opt=} {--multi=*} {--def=5}';

    public function testSavedScheduleWithEveryKindOfArgument(): void
    {
        $decorator = $this->decorator();
        $model = $decorator->getModel();

        $this
            ->post(route('chronos.update', $model), [
                'command_id' => $model->id,
                'time_method' => 'everyMinute',
                'run' => 'on',
                'without_overlapping_time' => 1440,
                'args' => [
                    'arg' => '0',
                    'list' => ['x', '', 'y'],
                    '--flag' => 'on',
                    '--opt' => 'v',
                    '--multi' => ['1', '', '2'],
                    '--def' => '',
                ],
            ])
            ->assertRedirect();

        $schedule = Schedule::firstOrFail();

        // "0" is a value, the empty values and the empty items are dropped
        $this->assertSame([
            'arg' => '0',
            'list' => ['x', 'y'],
            '--flag' => true,
            '--opt' => 'v',
            '--multi' => ['1', '2'],
        ], $schedule->args);

        $event = $this->app->make(ScheduleService::class)->register($this->app->make(ScheduleConsole::class), $schedule);

        $this->assertStringEndsWith("app:args 0 'x' 'y' --flag --opt='v' --multi='1' --multi='2'", $event->command);
        $this->assertSame('app:args 0 x y --flag --opt=v --multi=1 --multi=2', $decorator->getNameWithArguments($schedule->args));

        // The page shows the schedule, in code too
        $this
            ->get(route('chronos.edit', $model))
            ->assertOk()
            // The quotes are escaped by Blade, the arrow is the text of the template
            ->assertSee('&#039;--multi&#039; => [1, 2],', false)
            ->assertSee('[&#039;x&#039;, &#039;y&#039;],', false)
            ->assertSee('&#039;--flag&#039;,', false);
    }

    public function testCommandLineOfRealTimeHasOneTokenPerValue(): void
    {
        $decorator = $this->decorator();

        $cli = $decorator->getCliCommandToArray([
            'arg' => '0',
            'list' => ['x', 'y'],
            '--flag' => true,
            '--opt' => 'v',
            '--multi' => ['1', '2'],
            '--def' => null,
        ]);

        $this->assertSame(['app:args', '0', 'x', 'y', '--flag', '--opt=v', '--multi=1', '--multi=2'], array_slice($cli, 2));
        $this->assertSame([], $decorator->getArgumentsForExecToArray(['--flag' => false, '--multi' => [''], 'arg' => '']));
    }

    public function testRunFromQueueCleansTheArguments(): void
    {
        Bus::fake();
        $model = $this->decorator()->getModel();

        $this
            ->post(route('chronos.run', $model), [
                'args' => [
                    'arg' => '0',
                    'list' => ['x', '', 'y'],
                    '--flag' => 'on',
                    '--multi' => [''],
                    '--def' => '',
                ],
            ])
            ->assertRedirect();

        Bus::assertDispatched(CommandRunJob::class, function (CommandRunJob $job): bool {
            $args = (new ReflectionProperty($job, 'args'))->getValue($job);

            return $args === ['arg' => '0', 'list' => ['x', 'y'], '--flag' => true];
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('chronos.middlewares', [SubstituteBindings::class]);
    }

    private function decorator(): CommandDecorator
    {
        // Its own class: the classes of the commands of the other tests are already loaded with their signatures
        $this->makeCommand(name: 'ArgsKinds', command: self::SIGNATURE, withChronosTrait: true);
        $this->app->make(ArtisanRepositoryInterface::class)->load();

        return $this->app->make(CommandManager::class)->getByClass('App\\Console\\Commands\\ArgsKinds');
    }
}
