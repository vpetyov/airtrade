<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class SymlinkPacket
{
    /**
     * @var string
     */
    private $symlinkPath;

    /**
     * @var string
     */
    private $target;

    /**
     * @param string $symlinkPath
     * @param string $target
     */
    public function __construct($symlinkPath, $target)
    {
        $this->symlinkPath = $symlinkPath;
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getSymlinkPath()
    {
        return $this->symlinkPath;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }
}
