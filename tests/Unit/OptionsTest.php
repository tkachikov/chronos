<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit;

use Illuminate\Console\Command;
use PHPUnit\Framework\TestCase;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command as CommandModel;

final class OptionsTest extends TestCase
{
    public function testGettingRequiredOption(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--option}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertCount(0, $arguments);
        $this->assertCount(1, $options);
        $this->assertTrue(array_key_exists('option', $options));
        $this->assertEquals('option', $options['option']->getName());
        $this->assertFalse(method_exists($options['option'], 'isRequired'));
        $this->assertFalse($options['option']->isArray());
        $this->assertFalse($options['option']->getDefault());
    }

    public function testGettingOptionDescription(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--option : Test option}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $this->assertEquals('Test option', $options['option']->getDescription());
    }

    public function testGettingOptionWithNullableValue(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--option=}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $this->assertNull($options['option']->getDefault());
    }

    public function testGettingOptionWithValue(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--option=test}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $this->assertEquals('test', $options['option']->getDefault());
    }

    public function testGettingAnyArguments(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--option-one} {--option-two=} {--option-three=test}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $this->assertCount(3, $options);
    }
}