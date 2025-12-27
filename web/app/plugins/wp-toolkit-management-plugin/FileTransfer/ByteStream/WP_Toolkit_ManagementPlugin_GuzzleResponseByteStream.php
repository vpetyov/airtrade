<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\ByteStream;

use Psr\Http\Message\StreamInterface;

class GuzzleResponseByteStream implements ByteStreamInterface
{
    /**
     * @var StreamInterface
     */
    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @param int $bytes
     * @return string|null
     */
    public function read($bytes)
    {
        return $this->stream->read($bytes);
    }

    public function close()
    {
        $this->stream->close();
    }
}
