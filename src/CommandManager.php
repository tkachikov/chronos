<?php

declare(strict_types=1);

namespace Tkachikov\Chronos;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Repositories\ArtisanRepository;
use Tkachikov\Chronos\Repositories\CommandRepository;

final readonly class CommandManager
{
    public function __construct(
        private ArtisanRepository $artisanRepository,
        private CommandRepository $commandRepository,
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
            ->filter(fn(CommandDecorator $decorator) => $decorator->isNotSystem());
    }

    public function getChronos(): Collection
    {
        return $this
            ->get()
            ->filter(fn(CommandDecorator $decorator) => $decorator->isChronosCommands());
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
        $decorator = new CommandDecorator($command);
        $model = $this
            ->commandRepository
            ->getOrCreateByClass($command::class);
        $decorator->model($model);

        return $decorator;
    }
}