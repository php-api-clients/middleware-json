<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\JsonEncodeMiddleware;
use ApiClients\Middleware\Json\JsonStream;
use ApiClients\Tools\Json\JsonEncodeService;
use ApiClients\Tools\TestUtilities\TestCase;
use React\EventLoop\Factory;
use RingCentral\Psr7\BufferStream;
use RingCentral\Psr7\Request;
use function Clue\React\Block\await;

class JsonEncodeMiddlewareTest extends TestCase
{
    public function testPre()
    {
        $loop = Factory::create();
        $service = new JsonEncodeService($loop);
        $middleware = new JsonEncodeMiddleware($service);
        $stream = new JsonStream([]);
        $request = new Request('GET', 'https://example.com', [], $stream);

        $modifiedRequest = await($middleware->pre($request, 'abc'), $loop);
        self::assertSame(
            '[]',
            (string) $modifiedRequest->getBody()
        );
        self::assertTrue($modifiedRequest->hasHeader('Content-Type'));
        self::assertSame('application/json', $modifiedRequest->getHeaderLine('Content-Type'));
    }

    public function testPreNoJson()
    {
        $loop = Factory::create();
        $service = new JsonEncodeService($loop);
        $middleware = new JsonEncodeMiddleware($service);
        $stream = new BufferStream(2);
        $stream->write('yo');
        $request = new Request('GET', 'https://example.com', [], $stream);

        self::assertSame(
            $request,
            await(
                $middleware->pre($request, 'abc'),
                $loop
            )
        );
    }
}
