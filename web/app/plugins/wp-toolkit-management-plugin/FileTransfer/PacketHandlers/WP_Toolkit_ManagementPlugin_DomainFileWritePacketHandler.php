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
use PleskExt\WpToolkit\RemoteServer\Executor\ExecutorInterface;

/**
 * @deprecated
 * We plan to replace by LocalFileWritePacketHandler
 */
class DomainFileWritePacketHandler implements PacketHandler
{
    /**
     * @var ExecutorInterface
     */
    private $executor;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var string[]|null
     */
    private $currentFilePath;

    /**
     * @var string|null
     */
    private $currentFileContents;

    public function __construct(ExecutorInterface $executor, string $destinationPath)
    {
        $this->executor = $executor;
        $this->destinationPath = $destinationPath;
    }

    public function onDirectoryPacket(DirectoryPacket $packet): void
    {
        $path = $this->getFileManagerPath($packet->getDirectoryPath());
        if (!$this->executor->isExist($path)) {  // TODO check whether it's a directory, not a file or symlink
            $this->executor->createDirectory($path, true);
        }
    }

    /**
     * @param DirectoryEndPacket $packet
     */
    public function onDirectoryEndPacket(DirectoryEndPacket $packet)
    {
    }

    public function onFileStartPacket(FileStartPacket $packet): void
    {
        $this->currentFilePath = $this->getFileManagerPath($packet->getFilePath());
        $this->currentFileContents = '';
    }

    public function onFileContinuePacket(FileContinuePacket $packet): void
    {
        // TODO: Implement onFileContinuePacket() method.
    }

    public function onFileChunkPacket(FileChunkPacket $packet): void
    {
        if ($this->currentFilePath !== null) {
            $this->currentFileContents .= $packet->getData();
        }
    }

    public function onFileEndPacket(FileEndPacket $packet): void
    {
        if ($this->currentFilePath !== null) {
            $this->executor->uploadFileContents($this->currentFilePath, $this->currentFileContents);
        }

        $this->currentFilePath = null;
        $this->currentFileContents = null;
    }

    public function onSymlinkPacket(SymlinkPacket $packet): void
    {
        // TODO: Implement onSymlinkPacket() method.
    }

    public function onErrorPacket(ErrorPacket $packet): void
    {
        // TODO: Implement onErrorPacket() method.
    }

    public function onStreamEnd(): void
    {
        $this->currentFilePath = null;
        $this->currentFileContents = null;
    }

    private function getFileManagerPath(string $packetPath): string
    {
        if (!is_null($packetPath) && $packetPath !== '') {
            return $this->executor->joinPath([$this->destinationPath, $packetPath]);
        } else {
            return $this->destinationPath;
        }
    }

    public function onDirectoryListStartPacket(DirectoryListStartPacket $packet)
    {
        // TODO: Implement onDirectoryListStartPacket() method.
    }

    public function onDirectoryListChunkPacket(DirectoryListChunkPacket $packet)
    {
        // TODO: Implement onDirectoryListChunkPacket() method.
    }

    public function onDirectoryListEndPacket()
    {
        // TODO: Implement onDirectoryListEndPacket() method.
    }
}
