<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketWriter;

use PleskExt\WpToolkit\FileTransfer\Helper\JsonHelper;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataSender\HttpPacketRawDataSender;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataSender\PacketRawDataSenderInterface;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter\MemoryPacketRawDataStreamWriter;
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
use PleskExt\WpToolkit\FileTransfer\PacketTypes;

class DirectoryChunkedStreamPacketWriter implements FileSystemStreamPacketWriterInterface
{
    /**
     * @var HttpPacketRawDataSender
     */
    private $dataSender;

    /**
     * @var string[]
     */
    private $packets;

    /**
     * @var int
     */
    private $packetsLen;

    /**
     * @param PacketRawDataSenderInterface $dataSender
     */
    public function __construct($dataSender)
    {
        $this->dataSender = $dataSender;
        $this->packets = [];
        $this->packetsLen = 0;
    }

    public function writePacket($packet)
    {
        if ($packet instanceof DirectoryPacket) {
            $this->writePacketDirectory($packet->getDirectoryPath(), $packet->getMtime());
        } elseif ($packet instanceof DirectoryEndPacket) {
            $this->writePacketDirectoryEnd($packet->getDirectoryPath(), $packet->getMode());
        } elseif ($packet instanceof DirectoryListStartPacket) {
            $this->writePacketDirectoryListStart($packet->getDirectoryPath());
        } elseif ($packet instanceof DirectoryListChunkPacket) {
            $this->writePacketDirectoryListChunk($packet->getDirectoryListItems());
        } elseif ($packet instanceof DirectoryListEndPacket) {
            $this->writePacketDirectoryListEnd();
        } elseif ($packet instanceof FileStartPacket) {
            $this->writePacketFileStart($packet->getFilePath(), $packet->getMtime());
        } elseif ($packet instanceof FileContinuePacket) {
            $this->writePacketFileContinue($packet->getFilePath(), $packet->getStartPosition());
        } elseif ($packet instanceof FileChunkPacket) {
            $this->writePacketFileChunk($packet->getData());
        } elseif ($packet instanceof FileEndPacket) {
            $this->writePacketFileEnd($packet->getMode());
        } elseif ($packet instanceof SymlinkPacket) {
            $this->writePacketSymlink($packet->getSymlinkPath(), $packet->getTarget());
        } elseif ($packet instanceof ErrorPacket) {
            $this->writePacketError($packet->getCode(), $packet->getParams());
        } elseif ($packet instanceof StreamEndPacket) {
            $this->writePacketStreamEnd();
        } else {
            throw new \Exception("Internal error: invalid packet type");
        }
    }

    /**
     * @param string $directoryPath
     * @param string $mtime
     */
    public function writePacketDirectory($directoryPath, $mtime)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::DIRECTORY);
        $packetWriter->writeString($directoryPath);
        $packetWriter->writeString($mtime);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $directoryPath
     * @param string $mode
     */
    public function writePacketDirectoryEnd($directoryPath, $mode)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::DIRECTORY_END);
        $packetWriter->writeString($directoryPath);
        $packetWriter->writeString($mode);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $filePath
     * @param string $mtime
     */
    public function writePacketFileStart($filePath, $mtime)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::FILE_START);
        $packetWriter->writeString($filePath);
        $packetWriter->writeString($mtime);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $filePath
     * @param int $position
     */
    public function writePacketFileContinue($filePath, $position)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::FILE_CONTINUE);
        $packetWriter->writeInt($position);
        $packetWriter->writeString($filePath);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $chunk
     */
    public function writePacketFileChunk($chunk)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::FILE_CHUNK);
        $packetWriter->writeString($chunk);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $mode
     */
    public function writePacketFileEnd($mode)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::FILE_END);
        $packetWriter->writeString($mode);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $linkPath
     * @param string $target
     */
    public function writePacketSymlink($linkPath, $target)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::SYMLINK);
        $packetWriter->writeString($linkPath);
        $packetWriter->writeString($target);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $errorCode
     * @param array|null $errorParams
     */
    public function writePacketError($errorCode, $errorParams=null)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::ERROR);
        $packetWriter->writeString($errorCode);
        $packetWriter->writeString(JsonHelper::jsonEncodeUnicodeSafe($errorParams));
        $this->addPacket($packetWriter->getData());
    }

    public function writePacketStreamEnd()
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::STREAM_END);
        $this->addPacket($packetWriter->getData());
        $this->flush();
    }

    /**
     * @param string $path
     */
    public function writePacketDirectoryListStart($path)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::DIRECTORY_LIST_START);
        $packetWriter->writeString($path);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string[] $items
     */
    public function writePacketDirectoryListChunk($items)
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::DIRECTORY_LIST_CHUNK);
        $data = implode("\0", $items);
        $packetWriter->writeString($data);
        $this->addPacket($packetWriter->getData());
    }

    public function writePacketDirectoryListEnd()
    {
        $packetWriter = new MemoryPacketRawDataStreamWriter();
        $packetWriter->writePacketId(PacketTypes::DIRECTORY_LIST_END);
        $this->addPacket($packetWriter->getData());
    }

    /**
     * @param string $packetData
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function addPacket($packetData)
    {
        $this->packets[] = $packetData;
        $this->packetsLen += strlen($packetData);

        if ($this->packetsLen > 2 * 1024 * 1024) {
            $this->flush();
        }
    }

    private function flush()
    {
        $this->dataSender->sendPacketsData(implode("", $this->packets));
        $this->packets = [];
        $this->packetsLen = 0;
   }
}
