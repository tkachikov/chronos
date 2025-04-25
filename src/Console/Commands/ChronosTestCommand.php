<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Enums\TypeMessageEnum;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

final class ChronosTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:test';

    protected $description = 'Command for testing';

    public function handle(): int
    {
        foreach (TypeMessageEnum::cases() as $type) {
            $this->{$type->value === 'warning' ? 'warn' : $type->value}($type->name);
        }

        return self::SUCCESS;
    }
}
