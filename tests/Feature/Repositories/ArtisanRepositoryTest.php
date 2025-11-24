<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature\Repositories;

use Error;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class ArtisanRepositoryTest extends TestCase
{
    public function testInit(): void
    {
        $this->assertTrue(
            $this
                ->app
                ->bound(ArtisanRepositoryInterface::class),
        );

        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $this->assertSame(
            $repository,
            $this
                ->app
                ->make(ArtisanRepositoryInterface::class),
        );

        $reflection = new ReflectionProperty($repository, 'commands');

        $this->assertFalse($reflection->isInitialized($repository));

        $this
            ->app
            ->make(ArtisanRepositoryInterface::class)
            ->load();

        $this->assertTrue($reflection->isInitialized($repository));
    }

    public function testGettingEmptyCommands(): void
    {
        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $this->expectException(Error::class);

        $repository->get();
    }

    public function testGettingCommands(): void
    {
        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $repository->load();

        $commands = $repository->get();

        $this->assertNotEquals(0, count($commands));

        foreach ($commands as $key => $command) {
            $this->assertInstanceOf($key, $command);
            $this->assertTrue($command instanceof SymfonyCommand);
        }
    }
}
