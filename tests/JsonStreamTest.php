<?php
declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Json;

use ApiClients\Middleware\Json\JsonStream;
use ApiClients\Tools\TestUtilities\TestCase;

class JsonStreamTest extends TestCase
{
    public function testBasics()
    {
        $stream = new JsonStream([]);
        self::assertSame([], $stream->getJson());
        self::assertSame(2, $stream->getSize());
        self::assertSame('[]', $stream->getContents());
    }
}
