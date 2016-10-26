<?php declare(strict_types=1);

namespace ApiClients\Foundation\Transport;

use ApiClients\Foundation\Transport\CommandBus;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Uri;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;
use function WyriHaximus\React\futureFunctionPromise;

class Client
{
    const DEFAULT_OPTIONS = [
        Options::SCHEMA => 'https',
        Options::PATH => '/',
        Options::USER_AGENT => 'WyriHaximus/php-api-client',
        Options::HEADERS => [],
    ];

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var GuzzleClient
     */
    protected $handler;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var MiddlewareInterface[]
     */
    protected $middleware = [];

    /**
     * @param LoopInterface $loop
     * @param ContainerInterface $container
     * @param GuzzleClient $handler
     * @param array $options
     */
    public function __construct(
        LoopInterface $loop,
        ContainerInterface $container,
        GuzzleClient $handler,
        array $options = []
    ) {
        $this->loop = $loop;
        $this->container = $container;
        $this->handler = $handler;
        $this->options = $options + self::DEFAULT_OPTIONS;

        if (isset($this->options[Options::MIDDLEWARE])
        ) {
            $this->middleware = $this->options[Options::MIDDLEWARE];
        }
    }
    protected function preRequest(
        array $middlewares,
        RequestInterface $request,
        array $options
    ): CancellablePromiseInterface {
        $promise = resolve($request);

        foreach ($middlewares as $middleware) {
            $requestMiddleware = $middleware;
            $promise = $promise->then(function (RequestInterface $request) use ($options, $requestMiddleware) {
                return $requestMiddleware->pre($request, $options);
            });
        }

        return $promise;
    }

    protected function postRequest(
        array $middlewares,
        ResponseInterface $response,
        array $options
    ): CancellablePromiseInterface {
        $promise = resolve($response);

        foreach ($middlewares as $middleware) {
            $responseMiddleware = $middleware;
            $promise = $promise->then(function (ResponseInterface $response) use ($options, $responseMiddleware) {
                return $responseMiddleware->post($response, $options);
            });
        }

        return $promise;
    }

    protected function constructMiddlewares(array $options): array
    {
        $set = $this->middleware;

        if (isset($options[Options::MIDDLEWARE])) {
            $set = $options[Options::MIDDLEWARE];
        }

        $middlewares = [];
        foreach ($set as $middleware) {
            if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                continue;
            }

            $middlewares[] = $this->container->get($middleware);
        }

        return $middlewares;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     */
    public function request(RequestInterface $request, array $options = []): PromiseInterface
    {
        $request = $this->applyApiSettingsToRequest($request);
        $middlewares = $this->constructMiddlewares($options);

        return $this->preRequest($middlewares, $request, $options)->then(function ($request) use ($options) {
            return resolve($this->handler->sendAsync(
                $request,
                $options
            ));
        }, function (ResponseInterface $response) {
            return resolve($response);
        })->then(function (ResponseInterface $response) use ($middlewares, $options) {
            return $this->postRequest($middlewares, $response, $options);
        });
    }

    protected function streamBody(Response $response)
    {
        $stream = $response->getResponse()->getBody();
        $this->loop->addPeriodicTimer(0.001, function (TimerInterface $timer) use ($stream, $response) {
            if ($stream->eof()) {
                $timer->cancel();
                $response->emit('end');
                return;
            }

            $size = $stream->getSize();
            if ($size === 0) {
                return;
            }

            $response->emit('data', [$stream->read($size)]);
        });
    }

    protected function applyApiSettingsToRequest(RequestInterface $request): RequestInterface
    {
        $uri = $request->getUri();
        if (substr((string)$uri, 0, 4) !== 'http') {
            $uri = Uri::resolve(
                new Uri(
                    $this->options[Options::SCHEMA] .
                    '://' .
                    $this->options[Options::HOST] .
                    $this->options[Options::PATH]
                ),
                $request->getUri()
            );
        }

        return new Psr7Request(
            $request->getMethod(),
            $uri,
            $this->getHeaders() + $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        );
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [
            'User-Agent' => $this->options[Options::USER_AGENT],
        ];
        $headers += $this->options[Options::HEADERS];
        return $headers;
    }

    /**
     * @return string
     */
    public function getBaseURL(): string
    {
        return $this->options[Options::SCHEMA] . '://' . $this->options[Options::HOST] . $this->options[Options::PATH];
    }
}
