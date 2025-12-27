<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_StreamReader
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var int
     */
    private $readCount;

    /**
     * @param resource $stream
     * @param int $readCount
     */
    public function __construct($stream, $readCount = 4096)
    {
        $this->stream = $stream;
        $this->buffer = '';
        $this->readCount = $readCount;
    }

    /**
     * @param int $size
     * @return string
     */
    public function readCount($size)
    {
        while (strlen($this->buffer) < $size) {
            $newData = fread($this->stream, $this->readCount);

            if ($newData === '' || $newData === false) {
                break;
            }

            $this->buffer = $this->buffer . $newData;
        }

        $result = substr($this->buffer, 0, min($size, strlen($this->buffer)));
        $this->buffer = substr($this->buffer, min($size, strlen($this->buffer)));
        return $result;
    }

    /**
     * @return string
     */
    public function readAll()
    {
        while (true) {
            $newData = fread($this->stream, $this->readCount);

            if ($newData === '' || $newData === false) {
                break;
            }

            $this->buffer = $this->buffer . $newData;
        }

        $result = $this->buffer;
        $this->buffer = 0;
        return $result;
    }

    /**
     * @param string $char
     * @return string
     */
    public function readUntilChar($char)
    {
        while (true) {
            $newData = fread($this->stream, $this->readCount);
            if ($newData === '' || $newData === false) {
                break;
            }

            $charPosition = strpos($newData, $char);
            if ($charPosition !== false) {
                $result = $this->buffer . substr($newData, 0, $charPosition);
                $this->buffer = substr($newData, $charPosition + 1);
                return $result;
            } else {
                $this->buffer = $this->buffer . $newData;
            }
        }

        $result = $this->buffer;
        $this->buffer = '';
        return $result;
    }
}
