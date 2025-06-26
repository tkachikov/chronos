<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosOnlyOptionsTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:only-options {--test1} {--test2=test2} {--test3=}';

    protected $description = 'Test options';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->info('--test1: ' . $this->getOption('test1'));
        $this->info('--test2: ' . $this->getOption('test2'));
        $this->info('--test3: ' . $this->getOption('test3'));
    }

    /**
     * @throws Exception
     */
    private function getOption(string $key): string
    {
        $value = $this->option($key);

        if (!is_bool($value)) {
            return $value === null
                ? 'null'
                : (string) $value;
        }

        return match ($value) {
            true => 'true',
            false => 'false',
            default => throw new Exception('Not available option: ' . $key),
        };
    }
}