<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class DirectoryListChunkPacket
{
    /**
     * @var string[]
     */
    private $directoryListItems;

    /**
     * @param string[] $directoryListItems
     */
    public function __construct($directoryListItems)
    {
        $this->directoryListItems = $directoryListItems;
    }

    /**
     * @return string[]
     */
    public function getDirectoryListItems()
    {
        return $this->directoryListItems;
    }
}
