<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit\Repositories;

use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Repositories\ArtisanRepository;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;

final class ArtisanRepositoryTest extends TestCase
{
    public function testGettingList(): void
    {
        $this->assertTrue(
            $this
                ->app
                ->bound(ArtisanRepositoryInterface::class),
        );

        $repository = $this
            ->app
            ->make(ArtisanRepositoryInterface::class);

        $this->assertInstanceOf(ArtisanRepository::class, $repository);
        $this->assertSame(
            $repository,
            $this
                ->app
                ->make(ArtisanRepositoryInterface::class),
        );

        $commands = $repository->get();

        $this->assertNotEquals(0, count($commands));

        foreach ($commands as $key => $command) {
            $this->assertInstanceOf($key, $command);
            $this->assertTrue($command instanceof SymfonyCommand);
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ChronosServiceProvider::class,
        ];
    }
}
