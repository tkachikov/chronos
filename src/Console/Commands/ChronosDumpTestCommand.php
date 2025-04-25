<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Tkachikov\Chronos\Traits\ChronosRunnerTrait;

final class ChronosDumpTestCommand extends Command
{
    use ChronosRunnerTrait;

    protected $signature = 'chronos:dump';

    protected $description = 'Test dump';

    public function handle(): void
    {
        $this->dump('Test');
        $this->dump(null);
        $this->dump(123);
        $this->dump([1, 2, 3]);
        $this->dump((object) ['test' => 'Test', 'test2' => 123]);
    }
}