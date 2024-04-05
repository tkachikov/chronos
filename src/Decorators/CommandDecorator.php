<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use ReflectionObject;
use ReflectionException;
use Illuminate\Console\Command;
use Tkachikov\Chronos\Console\Commands\ChronosAnswerTestCommand;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Tkachikov\Chronos\Console\Commands\ChronosTestCommand;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tkachikov\Chronos\Console\Commands\ChronosFreeLogsCommand;
use Tkachikov\Chronos\Console\Commands\ChronosUpdateMetricsCommand;

class CommandDecorator
{
    private string $commandPath = 'App\\Console\\Commands\\';

    private CommandModel $model;

    public function __construct(
        private readonly Command|SymfonyCommand $command,
    ) {
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->command->$method(...$args);
    }

    public function model(CommandModel $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): CommandModel
    {
        if (!isset($this->model)) {
            $this->model = CommandModel::firstOrCreate(['class' => $this->command::class]);
        }

        return $this->model;
    }

    public function getShortName(): string
    {
        return str($this->getFullName())
            ->before('Command')
            ->after($this->getDirectory())
            ->toString();
    }

    public function getFullName(): string
    {
        return str($this->command::class)
            ->classBasename()
            ->toString();
    }

    public function getClassName(): string
    {
        return $this->command::class;
    }

    /**
     * @throws ReflectionException
     */
    public function getSignature(): string
    {
        $class = $this->getClassName();
        $object = new $class;
        $reflection = new ReflectionObject($object);

        return $reflection->getProperty('signature')->getValue($object);
    }

    public function getDirectory(): string
    {
        $file = str($this->command::class)
            ->replace('\\', '/')
            ->append('.php')
            ->toString();
        $afterPath = str($this->commandPath)
            ->substr(0, strlen($this->commandPath) - 1);
        $group = str(dirname($file))->replace('/', '\\')->after($afterPath);
        if ($group->startsWith('\\')) {
            $group = $group->substr(1);
        }
        $chronosPath = 'Tkachikov\\Chronos\\Console\\Commands';
        if ($group->startsWith($chronosPath)) {
            $group = $group->replace($chronosPath, 'Chronos');
        }

        return $group->toString();
    }

    public function runInSchedule(): bool
    {
        return !$this->notRunInSchedule();
    }

    public function notRunInSchedule(): bool
    {
        return $this->notRun(__FUNCTION__)
            && $this->customNotRun(__FUNCTION__);
    }

    public function runInManual(): bool
    {
        return !$this->notRunInManual();
    }

    public function notRunInManual(): bool
    {
        return $this->notRun(__FUNCTION__)
            && $this->customNotRun(__FUNCTION__);
    }

    public function isSystem(): bool
    {
        return !$this->isNotSystem();
    }

    public function isNotSystem(): bool
    {
        return str($this->command::class)->startsWith($this->commandPath);
    }

    public function isChronosCommands(): bool
    {
        return in_array($this->command::class, [
            ChronosTestCommand::class,
            ChronosFreeLogsCommand::class,
            ChronosAnswerTestCommand::class,
            ChronosUpdateMetricsCommand::class,
        ]);
    }

    private function notRun(string $attribute): bool
    {
        return in_array($attribute, $this->getAttributes())
            && $this->customNotRun(__FUNCTION__);
    }

    private function getAttributes(): array
    {
        return array_map(
            fn ($attribute) => str($attribute->getName())->classBasename(),
            (new ReflectionObject($this->command))->getAttributes(),
        );
    }

    private function customNotRun(string $method): bool
    {
        return !method_exists($this->command, $method)
            || $this->command->$method();
    }
}
