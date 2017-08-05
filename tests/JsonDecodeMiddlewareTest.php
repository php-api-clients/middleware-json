<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\JsonStream;
use ApiClients\Middleware\Json\JsonDecodeMiddleware;
use ApiClients\Middleware\Json\Options;
use ApiClients\Tools\Json\JsonDecodeService;
use ApiClients\Tools\TestUtilities\TestCase;
use Clue\React\Buzz\Message\ReadableBodyStream;
use React\EventLoop\Factory;
use React\Stream\ThroughStream;
use RingCentral\Psr7\Response;
use function Clue\React\Block\await;

class JsonDecodeMiddlewareTest extends TestCase
{
    public function provideValidJsonContentTypes()
    {
        yield ['application/json'];
        yield ['application/json; charset=utf-8'];
    }

    /**
     * @dataProvider provideValidJsonContentTypes
     */
    public function testPost(string $contentType)
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, ['Content-Type' => $contentType], '[]');

        $body = await(
            $middleware->post($response, 'abc'),
            $loop
        )->getBody();

        self::assertInstanceOf(JsonStream::class, $body);

        self::assertSame(
            [],
            $body->getJson()
        );
    }

    public function testPostNoContentType()
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, [], '[]');

        self::assertSame(
            $response,
            await(
                $middleware->post($response, 'abc'),
                $loop
            )
        );
    }

    public function testPostNoContentTypeCheck()
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, [], '[]');

        $body = await(
            $middleware->post(
                $response,
                'abc',
                [
                    JsonDecodeMiddleware::class => [
                        Options::NO_CONTENT_TYPE_CHECK => true,
                    ],
                ]
            ),
            $loop
        )->getBody();

        self::assertInstanceOf(JsonStream::class, $body);

        self::assertSame(
            [],
            $body->getJson()
        );
    }

    public function testPostCustomTYpe()
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, ['Content-Type' => 'custom/type'], '[]');

        $body = await(
            $middleware->post(
                $response,
                'abc',
                [
                    JsonDecodeMiddleware::class => [
                        Options::CONTENT_TYPE => 'custom/type',
                    ],
                ]
            ),
            $loop
        )->getBody();

        self::assertInstanceOf(JsonStream::class, $body);

        self::assertSame(
            [],
            $body->getJson()
        );
    }

    public function testPostNoJson()
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, [], new ReadableBodyStream(new ThroughStream()));

        self::assertSame(
            $response,
            await(
                $middleware->post($response, 'abc'),
                $loop
            )
        );
    }

    public function testPostEmpty()
    {
        $loop = Factory::create();
        $service = new JsonDecodeService($loop);
        $middleware = new JsonDecodeMiddleware($service);
        $response = new Response(200, [], '');

        self::assertSame(
            '',
            (string)await(
                $middleware->post($response, 'abc'),
                $loop
            )->getBody()
        );
    }
}
