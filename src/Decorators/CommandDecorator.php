<?php

declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Decorators;

use ReflectionObject;
use ReflectionException;
use Illuminate\Console\Command;
use Tkachikov\LaravelPulse\Models\Command as CommandModel;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CommandDecorator
{
    private string $commandPath = 'App\\Console\\Commands\\';

    private CommandModel $model;

    public function __construct(
        private readonly Command|SymfonyCommand $command,
    ) {
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->command->$method(...$args);
    }

    /**
     * @param CommandModel $model
     *
     * @return $this
     */
    public function model(CommandModel $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return CommandModel
     */
    public function getModel(): CommandModel
    {
        if (!isset($this->model)) {
            $this->model = CommandModel::firstOrCreate(['class' => $this->command::class]);
        }

        return $this->model;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return str($this->getFullName())->before('Command')->toString();
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return str($this->command::class)->classBasename()->toString();
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->command::class;
    }

    /**
     * @throws ReflectionException
     *
     * @return string
     */
    public function getSignature(): string
    {
        $class = $this->getClassName();
        $object = new $class;
        $reflection = new ReflectionObject($object);

        return $reflection->getProperty('signature')->getValue($object);
    }

    /**
     * @param Command $command
     *
     * @return string
     */
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

        return $group->toString();
    }

    /**
     * @return bool
     */
    public function runInSchedule(): bool
    {
        return !$this->notRunInSchedule();
    }

    /**
     * @return bool
     */
    public function notRunInSchedule(): bool
    {
        return $this->notRun(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function runInManual(): bool
    {
        return !$this->notRunInManual();
    }

    /**
     * @return bool
     */
    public function notRunInManual(): bool
    {
        return $this->notRun(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return !$this->isNotSystem($this->command);
    }

    /**
     * @return bool
     */
    public function isNotSystem(): bool
    {
        return str($this->command::class)->startsWith($this->commandPath);
    }

    /**
     * @param string $attribute
     *
     * @return bool
     */
    private function notRun(string $attribute): bool
    {
        return !empty((new ReflectionObject($this->command))->getAttributes($attribute));
    }
}
