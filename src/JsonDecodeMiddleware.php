<?php declare(strict_types=1);

namespace ApiClients\Middleware\Json;

use ApiClients\Foundation\Middleware\Annotation\First;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\PreTrait;
use ApiClients\Foundation\Middleware\Priority;
use ApiClients\Tools\Json\JsonDecodeService;
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
     * @First()
     */
    public function post(
        ResponseInterface $response,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if ($response->getBody() instanceof ReadableStreamInterface) {
            return resolve($response);
        }

        return $this->jsonDecodeService->decode((string)$response->getBody())->then(function ($json) use ($response) {
            $body = new JsonStream($json);
            return resolve($response->withBody($body));
        });
    }
}
