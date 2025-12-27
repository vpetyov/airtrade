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
use PleskExt\WpToolkit\FileTransfer\PacketWriter\FileSystemStreamPacketWriterInterface;

class ErrorBypassPacketHandler implements PacketHandler
{
    /**
     * @var FileSystemStreamPacketWriterInterface
     */
    private $packetWriter;

    /**
     * @param FileSystemStreamPacketWriterInterface $packetWriter
     */
    public function __construct($packetWriter)
    {
        $this->packetWriter = $packetWriter;
    }

    public function onDirectoryPacket(DirectoryPacket $packet)
    {
    }

    public function onDirectoryEndPacket(DirectoryEndPacket $packet)
    {
    }

    public function onFileStartPacket(FileStartPacket $packet)
    {
    }

    public function onFileContinuePacket(FileContinuePacket $packet)
    {
    }

    public function onFileChunkPacket(FileChunkPacket $packet)
    {
    }

    public function onFileEndPacket(FileEndPacket $packet)
    {
    }

    public function onSymlinkPacket(SymlinkPacket $packet)
    {
    }

    public function onErrorPacket(ErrorPacket $packet)
    {
        $this->packetWriter->writePacketError($packet->getCode(), $packet->getParams());
    }

    public function onStreamEnd()
    {
    }

    public function onDirectoryListStartPacket(DirectoryListStartPacket $packet)
    {
    }

    public function onDirectoryListChunkPacket(DirectoryListChunkPacket $packet)
    {
    }

    public function onDirectoryListEndPacket()
    {
    }
}
