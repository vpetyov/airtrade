<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketHandlers;

use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileContinuePacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\SymlinkPacket;

interface PacketHandler
{
    /**
     * @param DirectoryPacket $packet
     */
    public function onDirectoryPacket(DirectoryPacket $packet);

    /**
     * @param DirectoryEndPacket $packet
     */
    public function onDirectoryEndPacket(DirectoryEndPacket $packet);

    /**
     * @param FileStartPacket $packet
     */
    public function onFileStartPacket(FileStartPacket $packet);

    /**
     * @param FileContinuePacket $packet
     */
    public function onFileContinuePacket(FileContinuePacket $packet);

    /**
     * @param FileChunkPacket $packet
     */
    public function onFileChunkPacket(FileChunkPacket $packet);

    /**
     * @param FileEndPacket $packet
     */
    public function onFileEndPacket(FileEndPacket $packet);

    /**
     * @param SymlinkPacket $packet
     */
    public function onSymlinkPacket(SymlinkPacket $packet);

    /**
     * @param ErrorPacket $packet
     */
    public function onErrorPacket(ErrorPacket $packet);

    public function onStreamEnd();

    public function onDirectoryListStartPacket(DirectoryListStartPacket $packet);

    public function onDirectoryListChunkPacket(DirectoryListChunkPacket $packet);

    public function onDirectoryListEndPacket();
}
