<?php

declare(strict_types=1);

namespace Feature;

use Tkachikov\Chronos\Helpers\PhpHelper;
use Tkachikov\Chronos\Managers\CommandManager;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class PhpHelperTest extends TestCase
{
    public function testBinaryIsRunningPhpByDefault(): void
    {
        $this->assertSame(PHP_BINARY, $this->binary());
    }

    public function testBinaryFromConfig(): void
    {
        config(['chronos.php_binary' => '/usr/bin/php8.3']);

        $this->assertSame('/usr/bin/php8.3', $this->binary());
    }

    public function testCliCommandUsesBinary(): void
    {
        config(['chronos.php_binary' => '/usr/bin/php8.3']);

        $cli = $this
            ->app
            ->make(CommandManager::class)
            ->get()
            ->first()
            ->getCliCommandToArray();

        $this->assertSame('/usr/bin/php8.3', $cli[0]);
        $this->assertSame(base_path('artisan'), $cli[1]);
    }

    private function binary(): string
    {
        return $this
            ->app
            ->make(PhpHelper::class)
            ->getBinary();
    }
}
