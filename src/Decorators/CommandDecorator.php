<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Decorators;

use Exception;
use ReflectionException;
use ReflectionObject;
use Symfony\Component\Console\Command\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Helpers\ArgsHelper;
use Tkachikov\Chronos\Helpers\PhpHelper;
use Tkachikov\Chronos\Models\Command as CommandModel;

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

    /**
     * The tokens of the command line: a value of an array gives a token each (x y, --ids=1 --ids=2).
     *
     * @return list<string>
     */
    public function getArgumentsForExecToArray(array $args = []): array
    {
        $tokens = [];

        foreach ($args as $key => $value) {
            $tokens = [...$tokens, ...$this->prepareArgs((string) $key, $value)];
        }

        return $tokens;
    }

    public function getCliCommandToArray(
        array $args = [],
    ): array {
        return [
            app(PhpHelper::class)->getBinary(),
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
        $reflection = new ReflectionObject($this->command);

        return $reflection
            ->getProperty('signature')
            ->getValue($this->command);
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

    /**
     * App and Chronos commands are allowed, system commands only from the config.
     */
    public function isAllowed(): bool
    {
        return $this->isApp()
            || $this->isChronos()
            || $this->isAllowedSystem();
    }

    public function isAllowedSystem(): bool
    {
        $allowed = (array) config('chronos.system_commands', []);

        return in_array($this->getName(), $allowed, true)
            || in_array($this->command::class, $allowed, true);
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

    /**
     * @return list<string>
     */
    private function prepareArgs(string $key, mixed $value): array
    {
        if (ArgsHelper::isEmpty($value)) {
            return [];
        }

        return isset($this->command->getDefinition()->getArguments()[$key])
            ? $this->prepareArgument($value)
            : $this->prepareOption($key, $value);
    }

    /**
     * @return list<string>
     */
    private function prepareArgument(mixed $value): array
    {
        return array_map(
            fn ($v) => (string) $v,
            $this->values($value),
        );
    }

    /**
     * @return list<string>
     */
    private function prepareOption(string $key, mixed $value): array
    {
        if (str_starts_with($key, '--')) {
            $key = substr($key, 2);
        }
        $input = $this->command->getDefinition()->getOptions()[$key] ?? null;

        if ($input !== null && !$input->acceptValue()) {
            return $value ? ["--$key"] : [];
        }

        return array_map(
            fn ($v) => "--$key=$v",
            $this->values($value),
        );
    }

    /**
     * The values of an argument without the empty ones; "0" is a value.
     *
     * @return list<string|int|float>
     */
    private function values(mixed $value): array
    {
        return array_values(array_filter(
            is_array($value) ? $value : [$value],
            fn ($v) => !ArgsHelper::isEmpty($v),
        ));
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
