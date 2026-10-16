<?php

declare(strict_types=1);

namespace Feature;

use Tkachikov\Chronos\Providers\ChronosServiceProvider;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class PublishedConfigTest extends TestCase
{
    public function testConfigPublishedByOlderVersionGetsNewKeys(): void
    {
        // config/chronos.php published by 1.8
        config(['chronos' => [
            'domain' => null,
            'middlewares' => ['web', 'auth'],
        ]]);

        $this
            ->app
            ->getProvider(ChronosServiceProvider::class)
            ->register();

        $this->assertSame(['web', 'auth'], config('chronos.middlewares'));
        $this->assertSame([], config('chronos.system_commands'));
        $this->assertArrayHasKey('test_commands', config('chronos'));
        $this->assertArrayHasKey('php_binary', config('chronos'));
    }
}
