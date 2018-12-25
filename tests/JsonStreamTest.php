<?php
declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\JsonStream;
use ApiClients\Tools\TestUtilities\TestCase;

/**
 * @internal
 */
class JsonStreamTest extends TestCase
{
    public function testBasics(): void
    {
        $stream = new JsonStream([]);
        self::assertSame([], $stream->getParsedContents());
        self::assertSame(2, $stream->getSize());
        self::assertSame('[]', $stream->getContents());
    }
}
