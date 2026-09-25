<?php

declare(strict_types=1);

namespace Feature\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\User;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Models\Schedule;
use Tkachikov\Chronos\Repositories\ScheduleRepository;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class ScheduleRepositoryTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testSaveWithAuthenticatedUser(): void
    {
        $user = User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $this->save();

        $schedule = Schedule::firstOrFail();

        $this->assertSame($user->getKey(), $schedule->user_id);
        $this->assertSame($user->getMorphClass(), $schedule->user_type);
        $this->assertTrue($user->is($schedule->user));
    }

    /**
     * @throws BindingResolutionException
     */
    public function testSaveWithoutAuthenticatedUser(): void
    {
        $this->save();

        $schedule = Schedule::firstOrFail();

        $this->assertNull($schedule->user_id);
        $this->assertNull($schedule->user_type);
        $this->assertNull($schedule->user);
    }

    /**
     * @throws BindingResolutionException
     */
    private function save(): void
    {
        $command = Command::create(['class' => 'App\\Console\\Commands\\Test']);

        $this
            ->app
            ->make(ScheduleRepository::class)
            ->save([
                'command_id' => $command->id,
                'args' => null,
                'time_method' => 'everyMinute',
                'time_params' => null,
                'without_overlapping' => false,
                'without_overlapping_time' => 1440,
                'run_in_background' => false,
                'run' => true,
            ]);
    }
}
