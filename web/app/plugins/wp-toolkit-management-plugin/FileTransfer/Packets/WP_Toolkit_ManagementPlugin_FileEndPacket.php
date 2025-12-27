<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class FileEndPacket
{
    /**
     * @var string
     */
    private $mode;

    /**
     * @param string $mode
     */
    public function __construct($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }
}
