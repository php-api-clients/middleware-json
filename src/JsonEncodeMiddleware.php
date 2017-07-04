<?php declare(strict_types=1);

namespace ApiClients\Middleware\Json;

use ApiClients\Foundation\Middleware\Annotation\Third;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\PostTrait;
use ApiClients\Tools\Json\JsonEncodeService;
use Psr\Http\Message\RequestInterface;
use React\Promise\CancellablePromiseInterface;
use RingCentral\Psr7\BufferStream;
use function React\Promise\resolve;

class JsonEncodeMiddleware implements MiddlewareInterface
{
    use PostTrait;
    use ErrorTrait;

    /**
     * @var JsonEncodeService
     */
    private $jsonEncodeService;

    /**
     * @param JsonEncodeService $jsonEncodeService
     */
    public function __construct(JsonEncodeService $jsonEncodeService)
    {
        $this->jsonEncodeService = $jsonEncodeService;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return CancellablePromiseInterface
     *
     * @Third()
     */
    public function pre(
        RequestInterface $request,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if (!($request->getBody() instanceof JsonStream)) {
            return resolve($request);
        }

        return $this->jsonEncodeService->encode($request->getBody()->getJson())->then(function ($json) use ($request) {
            $body = new BufferStream(strlen($json));
            $body->write($json);
            return resolve($request->withBody($body)->withAddedHeader('Content-Type', 'application/json'));
        });
    }
}
