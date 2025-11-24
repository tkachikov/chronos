<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Feature\Repositories;

use Error;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class ArtisanRepositoryTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testInitialize(): void
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
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function testInitializeProperty(): void
    {
        $this
            ->app
            ->forgetInstance(ArtisanRepositoryInterface::class);

        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

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
        $this
            ->app
            ->forgetInstance(ArtisanRepositoryInterface::class);

        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $this->expectException(Error::class);

        $repository->get();
    }

    /**
     * @throws BindingResolutionException
     */
    public function testGettingCommands(): void
    {
        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $repository->load();

        $commands = $repository->get();

        $this->assertNotCount(0, $commands);

        foreach ($commands as $key => $command) {
            $this->assertInstanceOf($key, $command);
            $this->assertTrue($command instanceof SymfonyCommand);
        }
    }
}
