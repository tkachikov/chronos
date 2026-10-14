<?php

declare(strict_types=1);

namespace Feature;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tkachikov\Chronos\ChronosAuthentication;
use Tkachikov\Chronos\Http\Middleware\Authorize;
use Tkachikov\Chronos\Tests\Feature\TestCase;

final class AuthorizeTest extends TestCase
{
    public function testForbiddenOutsideLocalWithoutAuthCallback(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->handle();
            $this->fail('Request is not forbidden');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function testAllowedInLocalWithoutAuthCallback(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame('ok', $this->handle());
    }

    public function testAuthCallback(): void
    {
        $this->app['env'] = 'production';

        ChronosAuthentication::auth(fn () => true);

        $this->assertSame('ok', $this->handle());
    }

    protected function setUp(): void
    {
        parent::setUp();

        ChronosAuthentication::$auth = null;
    }

    protected function tearDown(): void
    {
        ChronosAuthentication::$auth = null;

        // migrations are rolled back without confirmation only outside production
        $this->app['env'] = 'testing';

        parent::tearDown();
    }

    private function handle(): mixed
    {
        return (new Authorize())->handle(
            Request::create('/chronos'),
            fn () => 'ok',
        );
    }
}
