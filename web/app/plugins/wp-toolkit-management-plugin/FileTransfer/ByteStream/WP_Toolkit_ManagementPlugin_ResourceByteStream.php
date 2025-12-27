<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\ByteStream;

use PleskExt\WpToolkit\FileTransfer\Exception\StreamEofException;
use PleskExt\WpToolkit\FileTransfer\Exception\StreamReadException;

class ResourceByteStream implements ByteStreamInterface
{
    /**
     * @var resource
     */
    private $stream;

    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $this->stream = $stream;
    }

    public function read($bytes)
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if ($bytes < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        if (0 === $bytes) {
            return '';
        }

        $string = '';
        do {
            $stringPart = @fread($this->stream, $bytes - strlen($string));
            if ($stringPart === false) {
                throw new StreamReadException('Unable to read from stream');
            }
            if ($stringPart === '') {
                throw new StreamEofException('No more data in the stream');
            }

            $string .= $stringPart;
        } while (strlen($string) !== $bytes);

        return $string;
    }

    public function close()
    {
        if (!is_resource($this->stream)) {
            return;
        }
        fclose($this->stream);
        $this->stream = null;
    }
}
