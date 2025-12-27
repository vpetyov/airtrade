<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketWriter;

use PleskExt\WpToolkit\FileTransfer\Helper\JsonHelper;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter\PacketRawDataStreamWriter;
use PleskExt\WpToolkit\FileTransfer\PacketTypes;

class DirectoryStreamPacketWriter implements FileSystemStreamPacketWriterInterface
{
    /**
     * @var PacketRawDataStreamWriter $streamPacketWriter
     */
    private $streamPacketWriter;

    /**
     * @param PacketRawDataStreamWriter $streamPacketWriter object capable of writing data to stream
     */
    public function __construct($streamPacketWriter)
    {
        $this->streamPacketWriter = $streamPacketWriter;
    }

    /**
     * @param string $directoryPath
     * @param string $mtime
     */
    public function writePacketDirectory($directoryPath, $mtime)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::DIRECTORY);
        $this->streamPacketWriter->writeString($directoryPath);
        $this->streamPacketWriter->writeString($mtime);
    }

    /**
     * @param string $directoryPath
     * @param string $mode
     */
    public function writePacketDirectoryEnd($directoryPath, $mode)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::DIRECTORY_END);
        $this->streamPacketWriter->writeString($directoryPath);
        $this->streamPacketWriter->writeString($mode);
    }

    /**
     * @param string $filePath
     * @param string $mtime
     */
    public function writePacketFileStart($filePath, $mtime)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::FILE_START);
        $this->streamPacketWriter->writeString($filePath);
        $this->streamPacketWriter->writeString($mtime);
    }

    /**
     * @param string $filePath
     * @param int $position
     */
    public function writePacketFileContinue($filePath, $position)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::FILE_CONTINUE);
        $this->streamPacketWriter->writeInt($position);
        $this->streamPacketWriter->writeString($filePath);
    }

    /**
     * @param string $chunk
     */
    public function writePacketFileChunk($chunk)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::FILE_CHUNK);
        $this->streamPacketWriter->writeString($chunk);
    }

    /**
     * @param string $mode
     */
    public function writePacketFileEnd($mode)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::FILE_END);
        $this->streamPacketWriter->writeString($mode);
    }

    /**
     * @param string $linkPath
     * @param string $target
     */
    public function writePacketSymlink($linkPath, $target)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::SYMLINK);
        $this->streamPacketWriter->writeString($linkPath);
        $this->streamPacketWriter->writeString($target);
    }

    /**
     * @param string $errorCode
     * @param array|null $errorParams
     */
    public function writePacketError($errorCode, $errorParams=null)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::ERROR);
        $this->streamPacketWriter->writeString($errorCode);
        $this->streamPacketWriter->writeString(JsonHelper::jsonEncodeUnicodeSafe($errorParams));
    }

    public function writePacketStreamEnd()
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::STREAM_END);
    }

    /**
     * @param string $path
     */
    public function writePacketDirectoryListStart($path)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::DIRECTORY_LIST_START);
        $this->streamPacketWriter->writeString($path);
    }

    /**
     * @param string[] $items
     */
    public function writePacketDirectoryListChunk($items)
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::DIRECTORY_LIST_CHUNK);
        $data = implode("\0", $items);
        $this->streamPacketWriter->writeString($data);
    }

    public function writePacketDirectoryListEnd()
    {
        $this->streamPacketWriter->writePacketId(PacketTypes::DIRECTORY_LIST_END);
    }
}
