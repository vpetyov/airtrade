<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\ByteStream;

class LookaheadStringByteStream implements ByteStreamInterface
{
    /**
     * @var string
     */
    private $str;

    /**
     * @var string
     */
    private $lookahead;

    public function __construct($str = '')
    {
        $this->str = $str;
        $this->lookahead = '';
    }

    /**
     * @param int $bytes
     * @return string|null
     */
    public function read($bytes)
    {
        $data = substr($this->str, 0, $bytes);
        $this->str = substr($this->str, $bytes);
        $this->lookahead .= $data;
        return $data;
    }

    /**
     * @param string $data
     */
    public function write($data)
    {
        $this->str .= $data;
    }

    public function commit()
    {
        $this->lookahead = '';
    }

    public function rollback()
    {
        $this->str = $this->lookahead . $this->str;
        $this->lookahead = '';
    }

    public function close()
    {
        $this->str = null;
    }
}
