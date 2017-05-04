<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\AcceptJsonMiddleware;
use ApiClients\Tools\TestUtilities\TestCase;
use React\EventLoop\Factory;
use RingCentral\Psr7\Request;
use function Clue\React\Block\await;

class AcceptJsonMiddlewareTest extends TestCase
{
    public function testPre()
    {
        $middleware = new AcceptJsonMiddleware();
        $request = new Request('GET', 'https://example.com', [], '');

        $modifiedRequest = await($middleware->pre($request), Factory::create());
        self::assertSame(
            [
                'Host' => ['example.com'],
                'Accept' => ['application/json'],
            ],
            $modifiedRequest->getHeaders()
        );
    }
}
