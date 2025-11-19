<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Decorators\CommandDecorator;
use Tkachikov\Chronos\Models\Command as CommandModel;

final class AttributesTest extends TestCase
{
    public function testGettingGroupNameByTest(): void
    {
        $command = new #[ChronosCommand(group: 'Test')] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('Test', $decorator->getGroupName());
    }

    public function testGettingGroupNameByTestItems(): void
    {
        $command = new #[ChronosCommand(group: 'TestItems')] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('TestItems', $decorator->getGroupName());
    }

    public function testGettingGroupNameByStringsAndInteger(): void
    {
        $command = new #[ChronosCommand(group: 'Strings and integer')] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertEquals('Strings and integer', $decorator->getGroupName());
    }

    public function testRunInManualInDefault(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
    }

    public function testRunInManualEnabled(): void
    {
        $command = new #[ChronosCommand(notRunInManual: false)] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
    }

    public function testRunInManualDisabled(): void
    {
        $command = new #[ChronosCommand(notRunInManual: true)] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInManual());
        $this->assertTrue($decorator->notRunInManual());
    }

    public function testRunInScheduleInDefault(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testRunInScheduleEnabled(): void
    {
        $command = new #[ChronosCommand(notRunInSchedule: false)] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testRunInScheduleDisabled(): void
    {
        $command = new #[ChronosCommand(notRunInSchedule: true)] class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInSchedule());
        $this->assertTrue($decorator->notRunInSchedule());
    }

    public function testRunAttributesInDefault(): void
    {
        $command = new class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
    }

    public function testRunAttributesEnabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: false,
                notRunInSchedule: false,
            )]
            class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testRunAttributesDisabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: true,
            )]
            class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInManual());
        $this->assertTrue($decorator->notRunInManual());
        $this->assertFalse($decorator->runInSchedule());
        $this->assertTrue($decorator->notRunInSchedule());
    }

    public function testRunAttributesManualIsDisabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: false,
            )]
            class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInManual());
        $this->assertTrue($decorator->notRunInManual());
        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testRunAttributesScheduleIsDisabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: false,
                notRunInSchedule: true,
            )]
            class extends Command {};
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
        $this->assertFalse($decorator->runInSchedule());
        $this->assertTrue($decorator->notRunInSchedule());
    }

    public function testNotRunInManualWithCustomIsEnabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: false,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return false;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
    }

    public function testNotRunInManualWithCustomIsDisabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: false,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return true;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInManual());
        $this->assertTrue($decorator->notRunInManual());
    }

    public function testNotRunInScheduleWithCustomIsEnabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: false,
                notRunInSchedule: true,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return false;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testNotRunInScheduleWithCustomIsDisabled(): void
    {
        $command = new
            #[ChronosCommand(
                notRunInManual: false,
                notRunInSchedule: true,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return true;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);

        $this->assertFalse($decorator->runInSchedule());
        $this->assertTrue($decorator->notRunInSchedule());
    }

    public function testNotRunAttributesWithCustomIsEnabled(): void
    {

        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: true,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return false;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);


        $this->assertTrue($decorator->runInManual());
        $this->assertFalse($decorator->notRunInManual());
        $this->assertTrue($decorator->runInSchedule());
        $this->assertFalse($decorator->notRunInSchedule());
    }

    public function testNotRunAttributesWithCustomIsDisabled(): void
    {

        $command = new
            #[ChronosCommand(
                notRunInManual: true,
                notRunInSchedule: true,
            )]
            class extends Command {
                public function notRun(): bool
                {
                    return true;
                }
            };
        $model = new CommandModel();
        $decorator = new CommandDecorator($command, $model);


        $this->assertFalse($decorator->runInManual());
        $this->assertTrue($decorator->notRunInManual());
        $this->assertFalse($decorator->runInSchedule());
        $this->assertTrue($decorator->notRunInSchedule());
    }
}