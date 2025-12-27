<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\PacketHandlers\PacketHandler;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileContinuePacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\StreamEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\SymlinkPacket;

class StreamUnpacker
{
    /**
     * @var PacketHandler
     */
    private $packetHandler;

    /**
     * @param PacketHandler $packetHandler
     */
    public function __construct(PacketHandler $packetHandler)
    {
        $this->packetHandler = $packetHandler;
    }

    /**
     * @param $byteStream
     * @throws \Exception
     */
    public function downloadFromStream($byteStream)
    {
        $streamPacketReader = new ByteStreamPacketReader($byteStream);

        while (true) {
            $packet = $streamPacketReader->nextPacket();

            if ($packet instanceof DirectoryPacket) {
                $this->packetHandler->onDirectoryPacket($packet);
            } else if ($packet instanceof DirectoryEndPacket) {
                $this->packetHandler->onDirectoryEndPacket($packet);
            } elseif ($packet instanceof DirectoryListStartPacket) {
                $this->packetHandler->onDirectoryListStartPacket($packet);
            } elseif ($packet instanceof DirectoryListChunkPacket) {
                $this->packetHandler->onDirectoryListChunkPacket($packet);
            } elseif ($packet instanceof DirectoryListEndPacket) {
                $this->packetHandler->onDirectoryListEndPacket($packet);
            } elseif ($packet instanceof FileStartPacket) {
                $this->packetHandler->onFileStartPacket($packet);
            } elseif ($packet instanceof FileContinuePacket) {
                $this->packetHandler->onFileContinuePacket($packet);
            } elseif ($packet instanceof FileChunkPacket) {
                $this->packetHandler->onFileChunkPacket($packet);
            } elseif ($packet instanceof FileEndPacket) {
                $this->packetHandler->onFileEndPacket($packet);
            } elseif ($packet instanceof SymlinkPacket) {
                $this->packetHandler->onSymlinkPacket($packet);
            } elseif ($packet instanceof ErrorPacket) {
                $this->packetHandler->onErrorPacket($packet);
            } elseif ($packet === null || $packet instanceof StreamEndPacket) {
                $this->packetHandler->onStreamEnd();
                break;
            }
        }
    }
}
