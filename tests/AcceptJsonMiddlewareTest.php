<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\AcceptJsonMiddleware;
use ApiClients\Tools\TestUtilities\TestCase;
use function Clue\React\Block\await;
use React\EventLoop\Factory;
use RingCentral\Psr7\Request;

/**
 * @internal
 */
class AcceptJsonMiddlewareTest extends TestCase
{
    public function testPre(): void
    {
        $middleware = new AcceptJsonMiddleware();
        $request = new Request('GET', 'https://example.com', [], '');

        $modifiedRequest = await($middleware->pre($request, 'abc'), Factory::create());
        self::assertSame(
            [
                'Host' => ['example.com'],
                'Accept' => ['application/json'],
            ],
            $modifiedRequest->getHeaders()
        );
    }
}
