<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class FileStartPacket
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $mtime;

    /**
     * @param string $filePath
     * @param string $mtime
     */
    public function __construct($filePath, $mtime)
    {
        $this->filePath = $filePath;
        $this->mtime = $mtime;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getMtime()
    {
        return $this->mtime;
    }
}
