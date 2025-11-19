<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit;

use Illuminate\Console\Command;
use PHPUnit\Framework\TestCase;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command as CommandModel;

final class ArgumentsTest extends TestCase
{
    public function testGettingRequiredArgument(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $options = $decorator
            ->getDefinition()
            ->getOptions();

        $this->assertCount(0, $options);
        $this->assertCount(1, $arguments);
        $this->assertTrue(array_key_exists('argument', $arguments));
        $this->assertEquals('argument', $arguments['argument']->getName());
        $this->assertTrue($arguments['argument']->isRequired());
        $this->assertFalse($arguments['argument']->isArray());
        $this->assertNull($arguments['argument']->getDefault());
    }

    public function testGettingOptionalArgument(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument?}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertFalse($arguments['argument']->isRequired());
    }

    public function testGettingArrayArgument(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument*}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertTrue($arguments['argument']->isArray());
    }

    public function testGettingDefaultArgument(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument=test}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertEquals('test', $arguments['argument']->getDefault());
    }

    public function testGettingAnyArguments(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument-one} {argument-two} {argument-three}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertCount(3, $arguments);
    }

    public function testGettingDescription(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {argument : Test argument}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $arguments = $decorator
            ->getDefinition()
            ->getArguments();

        $this->assertEquals(
            'Test argument',
            $arguments['argument']->getDescription(),
        );
    }
}