<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketHandlers;

use PleskExt\WpToolkit\DI\TypedContainer;
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

class PleskDomainFileWritePacketHandler implements PacketHandler
{
    /**
     * @var \pm_FileManager
     */
    private $fileManager;

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

    /**
     * @param string $domainName
     * @param string $destinationPath
     * @throws \Exception
     */
    public function __construct($domainName, $destinationPath)
    {
        $pmDomainFactory = TypedContainer::getPleskPmDomainFactory();
        $domain = $pmDomainFactory->getByName($domainName);
        $this->fileManager = new \pm_FileManager($domain->getId());
        $this->destinationPath = $destinationPath;
    }

    /**
     * @param DirectoryPacket $packet
     */
    public function onDirectoryPacket(DirectoryPacket $packet)
    {
        $path = $this->getFileManagerPath($packet->getDirectoryPath());
        if (!$this->fileManager->fileExists($path)) {  // TODO check whether it's a directory, not a file or symlink
            $this->fileManager->mkdir($path);
        }
    }

    public function onDirectoryEndPacket(DirectoryEndPacket $packet)
    {
    }

    /**
     * @param FileStartPacket $packet
     */
    public function onFileStartPacket(FileStartPacket $packet)
    {
        $this->currentFilePath = $this->getFileManagerPath($packet->getFilePath());
        $this->currentFileContents = '';
    }

    /**
     * @param FileContinuePacket $packet
     */
    public function onFileContinuePacket(FileContinuePacket $packet)
    {
        // TODO: Implement onFileContinuePacket() method.
    }

    /**
     * @param FileChunkPacket $packet
     */
    public function onFileChunkPacket(FileChunkPacket $packet)
    {
        if ($this->currentFilePath !== null) {
            $this->currentFileContents .= $packet->getData();
        }
    }

    /**
     * @param FileEndPacket $packet
     */
    public function onFileEndPacket(FileEndPacket $packet)
    {
        if ($this->currentFilePath !== null) {
            $this->fileManager->filePutContents($this->currentFilePath, $this->currentFileContents);
        }

        $this->currentFilePath = null;
        $this->currentFileContents = null;
    }

    /**
     * @param SymlinkPacket $packet
     */
    public function onSymlinkPacket(SymlinkPacket $packet)
    {
        // TODO: Implement onSymlinkPacket() method.
    }

    /**
     * @param ErrorPacket $packet
     */
    public function onErrorPacket(ErrorPacket $packet)
    {
        // TODO: Implement onErrorPacket() method.
    }

    public function onStreamEnd()
    {
        $this->currentFilePath = null;
        $this->currentFileContents = null;
    }

    /**
     * @param string $packetPath
     * @return string
     */
    private function getFileManagerPath($packetPath)
    {
        if (!is_null($packetPath) && $packetPath !== '') {
            return $this->destinationPath . DIRECTORY_SEPARATOR . $packetPath;
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
