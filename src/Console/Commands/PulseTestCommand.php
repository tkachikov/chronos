<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\LaravelPulse\Enums\TypeMessageEnum;
use Tkachikov\LaravelPulse\Traits\PulseRunnerTrait;

class PulseTestCommand extends Command
{
    use PulseRunnerTrait;

    protected $signature = 'pulse:test';

    protected $description = 'Command for testing';

    /**
     * @return int
     */
    public function handle(): int
    {
        foreach (TypeMessageEnum::cases() as $type) {
            $this->{$type->value === 'warning' ? 'warn' : $type->value}($type->name);
        }

        return self::SUCCESS;
    }
}
