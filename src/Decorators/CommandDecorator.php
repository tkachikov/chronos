<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use ReflectionObject;
use ReflectionException;
use Tkachikov\Chronos\Models\Command as CommandModel;
use Symfony\Component\Console\Command\Command;

class CommandDecorator
{
    private string $commandPath = 'App\\Console\\Commands\\';

    public function __construct(
        private readonly Command $command,
        private readonly CommandModel $model,
    ) {
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->command->$method(...$args);
    }

    public function getModel(): CommandModel
    {
        return $this->model;
    }

    public function getNameWithArguments(array $args = []): string
    {
        $args = $this->getArgumentsForExec($args);

        return $this->getName() . ($args ? " $args" : '');
    }

    public function getShortName(): string
    {
        $withoutPostfix = str($this->getFullName())->before('Command');
        $directory = str($this->getDirectory());
        $parentPlural = $directory->plural();
        $parentSingular = $directory->singular();
        $prefix = $withoutPostfix
            ->kebab()
            ->explode('-')
            ->first();
        $prefix = str($prefix)->studly();

        if ($parentPlural->is($prefix)) {
            return $withoutPostfix
                ->after($parentPlural)
                ->toString();
        }

        if ($parentSingular->isNotEmpty()) {
            return $withoutPostfix
                ->after($parentSingular)
                ->toString();
        }

        return $withoutPostfix->toString();
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
        return !$this->isApp();
    }

    public function isApp(): bool
    {
        return str($this->command::class)->startsWith('App\\');
    }

    public function isChronos(): bool
    {
        $nameSpace = str(__NAMESPACE__)->before('\\');

        return str($this->command::class)->startsWith($nameSpace);
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

    private function getArgumentsForExec(array $args = []): string
    {
        return implode(
            ' ',
            array_filter(
                array_map(
                    fn ($v, $k) => $this->prepareArgs($k, $v),
                    $args,
                    array_keys($args),
                ),
                fn ($v) => $v,
            ),
        );
    }

    private function prepareArgs(string $key, mixed $value): string
    {
        if (!$value) {
            return '';
        }

        return isset($this->command->getDefinition()->getArguments()[$key])
            ? $this->prepareArgument($value)
            : $this->prepareOption($key, $value);
    }

    private function prepareArgument(mixed $value): string
    {
        return is_array($value)
            ? implode(' ', $value)
            : (string) $value;
    }

    private function prepareOption(string $key, mixed $value): string
    {
        if (str_starts_with($key, '--')) {
            $key = substr($key, 2);
        }
        $input = $this->command->getDefinition()->getOptions()[$key];

        if (is_bool($input->getDefault()) && $value) {
            return "--$key";
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return implode(
            ' ',
            array_map(
                fn ($v) => "--$key=$v",
                $value,
            ),
        );
    }
}
