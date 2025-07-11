<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Managers;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Repositories\ArtisanRepositoryInterface;
use Tkachikov\Chronos\Repositories\CommandRepositoryInterface;

final readonly class CommandManager
{
    public function __construct(
        private ArtisanRepositoryInterface $artisanRepository,
        private CommandRepositoryInterface $commandRepository,
    ) {}

    /**
     * @return Collection<class-string, CommandDecorator>
     */
    public function get(): Collection
    {
        return $this
            ->artisanRepository
            ->get()
            ->map([$this, 'getDecorator']);
    }

    /**
     * @return Collection<class-string, CommandDecorator>
     */
    public function getSystems(): Collection
    {
        return $this
            ->get()
            ->filter(fn(CommandDecorator $decorator) => $decorator->isSystem());
    }

    public function getApps(): Collection
    {
        return $this
            ->get()
            ->filter(fn(CommandDecorator $decorator) => $decorator->isApp());
    }

    public function getChronos(): Collection
    {
        return $this
            ->get()
            ->filter(fn(CommandDecorator $decorator) => $decorator->isChronos());
    }

    /**
     * @param class-string $class
     */
    public function getByClass(string $class): CommandDecorator
    {
        return $this
            ->get()
            ->get($class);
    }

    public function getDecorator(Command $command): CommandDecorator
    {
        $model = $this
            ->commandRepository
            ->getOrCreateByClass($command::class);

        return new CommandDecorator($command, $model);
    }
}