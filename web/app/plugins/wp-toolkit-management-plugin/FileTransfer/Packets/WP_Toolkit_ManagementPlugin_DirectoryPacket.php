<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class DirectoryPacket
{
    /**
     * @var string
     */
    private $directoryPath;

    /**
     * @var string
     */
    private $mtime;

    /**
     * @param string $directoryPath
     * @param string $mtime
     */
    public function __construct($directoryPath, $mtime)
    {
        $this->directoryPath = $directoryPath;
        $this->mtime = $mtime;
    }

    /**
     * @return string
     */
    public function getDirectoryPath()
    {
        return $this->directoryPath;
    }

    /**
     * @return string
     */
    public function getMtime()
    {
        return $this->mtime;
    }
}
