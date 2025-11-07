<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Attributes\ChronosCommand;

#[ChronosCommand(
    group: 'Chronos',
)]
final class ChronosInstallCommand extends Command
{
    protected $signature = 'chronos:install';

    protected $description = 'Install all Chronos resources';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'chronos-provider']);

        $this->call('vendor:publish', ['--tag' => 'chronos-config']);

        $this->call('migrate', [
            '--path' => 'vendor/tkachikov/chronos/database/migrations',
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
