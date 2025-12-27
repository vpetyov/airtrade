<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class DirectoryEndPacket
{
    /**
     * @var string
     */
    private $directoryPath;

    /**
     * @var string
     */
    private $mode;

    /**
     * @param string $directoryPath
     * @param string $mode
     */
    public function __construct($directoryPath, $mode)
    {
        $this->directoryPath = $directoryPath;
        $this->mode = $mode;
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
    public function getMode()
    {
        return $this->mode;
    }
}
