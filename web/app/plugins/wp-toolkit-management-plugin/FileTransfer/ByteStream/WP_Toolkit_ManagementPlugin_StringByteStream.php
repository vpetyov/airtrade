<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\ByteStream;

class StringByteStream implements ByteStreamInterface
{
    /**
     * @var string
     */
    private $str;

    public function __construct($str)
    {
        $this->str = $str;
    }

    /**
     * @param int $bytes
     * @return string|null
     */
    public function read($bytes)
    {
        $data = substr($this->str, 0, $bytes);
        $this->str = substr($this->str, $bytes);
        return $data;
    }

    public function close()
    {
        $this->str = null;
    }
}
