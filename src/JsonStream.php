<?php declare(strict_types=1);

namespace ApiClients\Middleware\Json;

use Psr\Http\Message\StreamInterface;
use RingCentral\Psr7\BufferStream;

class JsonStream implements StreamInterface
{
    /**
     * @var array
     */
    private $json = [];

    /**
     * @var StreamInterface
     */
    private $bufferStream;

    public function __construct(array $json)
    {
        $this->json = $json;
        $jsonString = json_encode($json);
        $this->bufferStream = new BufferStream(strlen($jsonString));
        $this->bufferStream->write($jsonString);
    }

    /**
     * @return array
     */
    public function getJson()
    {
        return $this->json;
    }

    public function __toString()
    {
        return $this->getContents();
    }

    public function getContents()
    {
        return $this->bufferStream->getContents();
    }

    public function close()
    {
    }

    public function detach()
    {
    }

    public function getSize()
    {
        return $this->bufferStream->getSize();
    }

    public function isReadable()
    {
        return $this->bufferStream->isReadable();
    }

    public function isWritable()
    {
        return $this->bufferStream->isWritable();
    }

    public function isSeekable()
    {
        return $this->bufferStream->isSeekable();
    }

    public function rewind()
    {
        return $this->bufferStream->rewind();
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return $this->bufferStream->seek($offset, $whence);
    }

    public function eof()
    {
        return $this->bufferStream->eof();
    }

    public function tell()
    {
        return $this->bufferStream->tell();
    }

    /**
     * Reads data from the buffer.
     */
    public function read($length)
    {
        return $this->bufferStream->read($length);
    }

    /**
     * Writes data to the buffer.
     */
    public function write($string)
    {
        return $this->bufferStream->write($string);
    }

    public function getMetadata($key = null)
    {
        return $this->bufferStream->getMetadata($key);
    }
}
