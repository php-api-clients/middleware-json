<?php declare(strict_types=1);

namespace ApiClients\Middleware\Json;

use ApiClients\Foundation\Middleware\Annotation\ThirdLast;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\PreTrait;
use ApiClients\Tools\Json\JsonDecodeService;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\ResponseInterface;
use React\Promise\CancellablePromiseInterface;
use React\Stream\ReadableStreamInterface;
use function React\Promise\resolve;

class JsonDecodeMiddleware implements MiddlewareInterface
{
    use PreTrait;
    use ErrorTrait;

    /**
     * @var JsonDecodeService
     */
    private $jsonDecodeService;

    /**
     * JsonDecode constructor.
     * @param JsonDecodeService $jsonDecodeService
     */
    public function __construct(JsonDecodeService $jsonDecodeService)
    {
        $this->jsonDecodeService = $jsonDecodeService;
    }

    /**
     * @param ResponseInterface $response
     * @param array $options
     * @return CancellablePromiseInterface
     *
     * @ThirdLast()
     */
    public function post(
        ResponseInterface $response,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if ($response->getBody() instanceof ReadableStreamInterface) {
            return resolve($response);
        }

        $body = (string)$response->getBody();
        if ($body === '') {
            $stream = new BufferStream(0);
            $stream->write($body);
            return resolve($response->withBody($stream));
        }

        return $this->jsonDecodeService->decode($body)->then(function ($json) use ($response) {
            $body = new JsonStream($json);
            return resolve($response->withBody($body));
        });
    }
}
