<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use Exception;
use ReflectionObject;
use ReflectionException;
use Tkachikov\Chronos\Attributes\ChronosCommand;
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

    public function getArgumentsForExec(array $args = []): string
    {
        return implode(
            ' ',
            $this->getArgumentsForExecToArray($args),
        );
    }

    public function getArgumentsForExecToArray(array $args = []): array
    {
        return array_filter(
            array_map(
                fn ($v, $k) => $this->prepareArgs($k, $v),
                $args,
                array_keys($args),
            ),
            fn ($v) => $v,
        );
    }

    public function getCliCommandToArray(
        array $args = [],
    ): array {
        return [
            'php',
            base_path('artisan'),
            $this->getName(),
            ...$this->getArgumentsForExecToArray($args),
        ];
    }

    public function getCliCommandToString(
        array $args = [],
    ): string {
        return implode(
            ' ',
            $this->getCliCommandToArray($args),
        );
    }

    public function getShortName(): string
    {
        $withoutPostfix = str($this->getFullName())->before('Command');

        $groupName = $this->getGroupName();
        $directory = $this->getDirectory();
        $parentPrefix = str($groupName ?? $directory);
        $parentPlural = $parentPrefix->plural();
        $parentSingular = $parentPrefix->singular();

        $after = '';

        if ($withoutPostfix->startsWith($parentSingular)) {
            $after = $parentSingular;
        }

        if ($withoutPostfix->startsWith($parentPlural)) {
            $after = $parentPlural;
        }

        $shortName = $withoutPostfix
            ->after($after)
            ->toString();

        return $shortName ?: $withoutPostfix->toString();
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

        return $group->toString();
    }

    public function getGroupName(): ?string
    {
        return $this
            ->getChronosCommandAttribute()
            ?->group;
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

    /**
     * @throws Exception
     */
    private function notRun(string $attribute): bool
    {
        $chronosCommandAttribute = $this->getChronosCommandAttribute();

        if ($chronosCommandAttribute === null) {
            return in_array($attribute, $this->getAttributes())
                && $this->customNotRun(__FUNCTION__);
        }

        $notRun = match ($attribute) {
            'notRunInManual' => $chronosCommandAttribute->notRunInManual,
            'notRunInSchedule' => $chronosCommandAttribute->notRunInSchedule,
        };

        return $notRun
            && $this->customNotRun(__FUNCTION__);
    }

    /**
     * @deprecated Starts with version 1.4.7
     */
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

    /**
     * @throws Exception
     */
    private function getChronosCommandAttribute(): ?ChronosCommand
    {
        $reflection = new ReflectionObject($this->command);
        $attributes = $reflection->getAttributes(ChronosCommand::class);

        if (empty($attributes)) {
            return null;
        }

        if (count($attributes) > 1) {
            throw new Exception('ChronosCommand attribute must be used only once.');
        }

        return $attributes[0]->newInstance();
    }
}
