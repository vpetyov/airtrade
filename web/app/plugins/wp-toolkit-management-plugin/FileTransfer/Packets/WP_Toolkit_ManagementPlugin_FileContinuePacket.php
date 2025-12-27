<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class FileContinuePacket
{
    /**
     * @var int
     */
    private $startPosition;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @param int $startPosition
     * @param string $filePath
     */
    public function __construct($startPosition, $filePath)
    {
        $this->startPosition = $startPosition;
        $this->filePath = $filePath;
    }

    /**
     * @return int
     */
    public function getStartPosition()
    {
        return $this->startPosition;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }
}
