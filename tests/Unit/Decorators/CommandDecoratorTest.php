<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit\Decorators;

use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tkachikov\Chronos\Console\Commands\ChronosInstallCommand;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command as CommandModel;

final class CommandDecoratorTest extends TestCase
{
    public static function getCommandNames(): array
    {
        return [
            ['name' => 'TestName'],
            ['name' => 'test:name'],
            ['name' => 'test-name'],
            ['name' => 'app:test'],
            ['name' => 'app:test-name'],
            ['name' => 'app:test-name-with-args'],
        ];
    }

    public static function getCommandSignatures(): array
    {
        return [
            ['signature' => 'test'],
            ['signature' => 'app:test'],
            ['signature' => 'test {argument}'],
            ['signature' => 'app:test {--option}'],
            ['signature' => 'test {argument} {argument?}'],
            ['signature' => 'app:test {argument} {--option=} {argument?}'],
            ['signature' => 'app:test {argument} {--option=} {argument=test}'],
            ['signature' => 'app:test {argumentArray*}'],
            ['signature' => 'app:test {argumentArray?*}'],
            ['signature' => 'app:test {--option= : Test option}'],
        ];
    }

    public function testDecorateCallableMethods(): void
    {
        $command = new class extends Command {
            public function test(): string
            {
                return 'ok';
            }

            public function testWithArg(string $ping): string
            {
                return match ($ping) {
                    'ping' => 'pong',
                    default => 'ok',
                };
            }

            public function testWithAnyArgs(
                string $ping,
                int $number,
            ): string {
                return $this->testWithArg($ping)
                    . ': ' . $number;
            }
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('ok', $decorator->test());
        $this->assertEquals('pong', $decorator->testWithArg('ping'));
        $this->assertEquals('ok', $decorator->testWithArg('ok?'));
        $this->assertEquals('pong: 5', $decorator->testWithAnyArgs('ping', 5));
    }

    public function testGetCommandModel(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertSame($model, $decorator->getModel());
    }

    public function testGettingNullableNameOfSignature(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertNull($decorator->getName());
    }

    #[DataProvider('getCommandNames')]
    /**
     * @dataProvider getCommandNames
     */
    public function testGettingNameOfSignature(
        string $name,
    ): void {
        $command = new class($name) extends Command {
            public function __construct(string $name)
            {
                $this->signature = $name;
                parent::__construct();
            }
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals($name, $decorator->getName());
    }

    public function testGettingCommandName(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $expected = str($command::class)
            ->classBasename()
            ->toString();

        $this->assertEquals($expected, $decorator->getFullName());
    }

    #[DataProvider('getCommandSignatures')]
    /**
     * @dataProvider getCommandSignatures
     */
    public function testGettingSignature(
        string $signature,
    ): void {
        $command = new class extends Command {
            protected $signature;

            public function setSignature(string $signature): void
            {
                $this->signature = $signature;
            }
        };
        $command->setSignature($signature);
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals($signature, $decorator->getSignature());
    }

    public function testGettingClassName(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals($command::class, $decorator->getClassName());
    }

    public function testGettingArgumentsForExecToArrayForArguments(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {test=defaultValue}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertCount(
            0,
            $decorator->getArgumentsForExecToArray(['test' => false]),
        );
        $this->assertCount(
            1,
            $decorator->getArgumentsForExecToArray(['test' => true]),
        );
        $this->assertEquals(
            '1',
            array_values(
                $decorator->getArgumentsForExecToArray(['test' => true]),
            )[0],
        );
        $this->assertEquals(
            'value',
            array_values(
                $decorator->getArgumentsForExecToArray(['test' => 'value']),
            )[0],
        );
    }

    public function testGettingArgumentsForExecToArrayForOptions(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--test}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertCount(
            0,
            $decorator->getArgumentsForExecToArray(['test' => false]),
        );

        $this->assertCount(
            0,
            $decorator->getArgumentsForExecToArray(['--test' => false]),
        );
        $this->assertCount(
            1,
            $decorator->getArgumentsForExecToArray(['test' => true]),
        );
        $this->assertCount(
            1,
            $decorator->getArgumentsForExecToArray(['--test' => true]),
        );
        $this->assertEquals(
            '--test',
            array_values(
                $decorator->getArgumentsForExecToArray(['test' => true]),
            )[0],
        );
        $this->assertEquals(
            '--test',
            array_values(
                $decorator->getArgumentsForExecToArray(['--test' => true]),
            )[0],
        );
    }

    public function testGettingArgumentsForExecToArrayForOptionsWithValue(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {--test=defaultValue}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertCount(
            0,
            $decorator->getArgumentsForExecToArray(['test' => false]),
        );

        $this->assertCount(
            0,
            $decorator->getArgumentsForExecToArray(['--test' => false]),
        );
        $this->assertCount(
            1,
            $decorator->getArgumentsForExecToArray(['test' => true]),
        );
        $this->assertCount(
            1,
            $decorator->getArgumentsForExecToArray(['--test' => true]),
        );
        $this->assertEquals(
            '--test=1',
            array_values(
                $decorator->getArgumentsForExecToArray(['test' => true]),
            )[0],
        );
        $this->assertEquals(
            '--test=1',
            array_values(
                $decorator->getArgumentsForExecToArray(['--test' => true]),
            )[0],
        );
        $this->assertEquals(
            '--test=value',
            array_values(
                $decorator->getArgumentsForExecToArray(['test' => 'value']),
            )[0],
        );
        $this->assertEquals(
            '--test=value',
            array_values(
                $decorator->getArgumentsForExecToArray(['--test' => 'value']),
            )[0],
        );
    }

    public function testGettingArgumentsForExec(): void
    {
        $command = new class extends Command {
            protected $signature = 'app:test {userId} {--notification}';
        };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals(
            '13 --notification',
            $decorator->getArgumentsForExec([
                'userId' => 13,
                '--notification' => true,
            ]),
        );
    }

    public function testGettingChronosCommandName(): void
    {
        $command = new ChronosInstallCommand();
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('chronos:install', $decorator->getName());
    }

    public function testGettingChronosCommandFullName(): void
    {
        $command = new ChronosInstallCommand();
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('ChronosInstallCommand', $decorator->getFullName());
    }

    public function testGettingChronosClassName(): void
    {
        $command = new ChronosInstallCommand();
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals(
            'Tkachikov\Chronos\Console\Commands\ChronosInstallCommand',
            $decorator->getClassName(),
        );
    }

    public function testGettingChronosCommandDirectory(): void
    {
        $command = new ChronosInstallCommand();
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals(
            'Tkachikov\Chronos\Console\Commands',
            $decorator->getDirectory(),
        );
    }

    public function testGettingChronosShortName(): void
    {
        $command = new ChronosInstallCommand();
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals(
            'Install',
            $decorator->getShortName(),
        );
    }
}