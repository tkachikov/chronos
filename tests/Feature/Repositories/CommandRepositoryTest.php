<?php

declare(strict_types=1);

namespace Feature\Repositories;

use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionException;
use ReflectionProperty;
use Tkachikov\Chronos\Models\Command;
use Tkachikov\Chronos\Repositories\CommandRepositoryInterface;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class CommandRepositoryTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testInitialize(): void
    {
        $this->assertTrue(
            $this
                ->app
                ->bound(CommandRepositoryInterface::class),
        );

        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $this->assertSame(
            $repository,
            $this
                ->app
                ->make(CommandRepositoryInterface::class),
        );
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function testInitializeProperty(): void
    {
        $this
            ->app
            ->forgetInstance(CommandRepositoryInterface::class);

        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $reflection = new ReflectionProperty($repository, 'commands');

        $this->assertFalse($reflection->isInitialized($repository));

        $repository->load();

        $this->assertTrue($reflection->isInitialized($repository));
    }

    /**
     * @throws BindingResolutionException
     */
    public function testGettingEmptyCommands(): void
    {
        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $repository->load();

        $this->assertCount(0, $repository->get());
    }

    /**
     * @throws BindingResolutionException
     */
    public function testCreatingCommand(): void
    {
        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $repository->load();

        $command = $repository->getOrCreateByClass('Test');

        $this->assertInstanceOf(Command::class, $command);

        $commands = $repository->get();

        $this->assertCount(1, $commands);
        $this->assertSame($command, $commands->first());
    }

    /**
     * @throws BindingResolutionException
     */
    public function testGettingCommandAfterCreate(): void
    {
        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $repository->load();

        $command = $repository->getOrCreateByClass('Test');

        $this->assertTrue($command->wasRecentlyCreated);

        $this
            ->app
            ->forgetInstance(CommandRepositoryInterface::class);

        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $repository->load();

        $command = $repository->getOrCreateByClass('Test');

        $this->assertFalse($command->wasRecentlyCreated);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testCreatesAnyCommands(): void
    {
        $repository = $this
            ->app
            ->make(CommandRepositoryInterface::class);

        $repository->load();

        $command = $repository->getOrCreateByClass('Test');
        $commandTwo = $repository->getOrCreateByClass('Test2');

        $this->assertCount(2, $repository->get());
        $this->assertTrue(
            $repository
                ->getOrCreateByClass('Test')
                ->is($command),
        );
        $this->assertTrue(
            $repository
                ->getOrCreateByClass('Test2')
                ->is($commandTwo),
        );
    }
}
