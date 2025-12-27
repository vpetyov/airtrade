<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketHandlers;

use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileContinuePacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\StreamEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\SymlinkPacket;

class PacketTypeRouter
{
    public function route($packet, PacketHandler $packetHandler)
    {
        if ($packet instanceof DirectoryPacket) {
            $packetHandler->onDirectoryPacket($packet);
        } else if ($packet instanceof DirectoryEndPacket) {
            $packetHandler->onDirectoryEndPacket($packet);
        } elseif ($packet instanceof FileStartPacket) {
            $packetHandler->onFileStartPacket($packet);
        } elseif ($packet instanceof FileContinuePacket) {
            $packetHandler->onFileContinuePacket($packet);
        } elseif ($packet instanceof FileChunkPacket) {
            $packetHandler->onFileChunkPacket($packet);
        } elseif ($packet instanceof FileEndPacket) {
            $packetHandler->onFileEndPacket($packet);
        } elseif ($packet instanceof SymlinkPacket) {
            $packetHandler->onSymlinkPacket($packet);
        } elseif ($packet instanceof ErrorPacket) {
            $packetHandler->onErrorPacket($packet);
        } elseif ($packet === null || $packet instanceof StreamEndPacket) {
            $packetHandler->onStreamEnd();
        } else {
            throw new \Exception('Unknown packet type');
        }
    }
}
