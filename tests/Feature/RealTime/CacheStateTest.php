<?php

declare(strict_types=1);

namespace Feature\RealTime;

use Illuminate\Foundation\Auth\User;
use Tkachikov\Chronos\Dto\RunDto;
use Tkachikov\Chronos\Enums\AnswerState;
use Tkachikov\Chronos\Services\RealTime\CacheService;
use Tkachikov\Chronos\Services\RealTime\StateService;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class CacheStateTest extends TestCase
{
    public function testStateIsStoredWithoutObjectsInCache(): void
    {
        // like a Laravel 13 app: cached objects are not unserialized
        config([
            'cache.default' => 'array',
            'cache.stores.array.serialize' => true,
            'cache.serializable_classes' => false,
        ]);
        $this->app['cache']->forgetDriver('array');

        $user = User::forceCreate([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'secret',
        ]);

        $this
            ->app
            ->make(CacheService::class)
            ->set(new RunDto(
                commandId: 1,
                user: $user,
                args: ['--id' => '7'],
                logs: ['first'],
                pid: 123,
                answerState: AnswerState::Pending,
            ));

        $state = StateService::make(1);

        $this->assertSame(['--id' => '7'], $state->getArgs());
        $this->assertSame(['first'], $state->getLogs());
        $this->assertSame(123, $state->getPid());
        $this->assertTrue($state->isPending());
        $this->assertTrue($user->is($state->getUser()));
    }
}
