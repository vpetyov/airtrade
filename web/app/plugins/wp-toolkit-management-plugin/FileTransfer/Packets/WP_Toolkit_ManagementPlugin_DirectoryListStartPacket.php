<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class DirectoryListStartPacket
{
    /**
     * @var string
     */
    private $directoryPath;

    /**
     * @param string $directoryPath
     */
    public function __construct($directoryPath)
    {
        $this->directoryPath = $directoryPath;
    }

    /**
     * @return string
     */
    public function getDirectoryPath()
    {
        return $this->directoryPath;
    }
}
