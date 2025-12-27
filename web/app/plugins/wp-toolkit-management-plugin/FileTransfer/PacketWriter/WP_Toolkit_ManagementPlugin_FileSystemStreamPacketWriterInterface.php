<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketWriter;

interface FileSystemStreamPacketWriterInterface
{
    /**
     * @param string $directoryPath
     * @param string $mtime
     */
    public function writePacketDirectory($directoryPath, $mtime);

    /**
     * @param string $directoryPath
     * @param string $mode
     */
    public function writePacketDirectoryEnd($directoryPath, $mode);

    /**
     * @param string $filePath
     * @param string $mtime
     */
    public function writePacketFileStart($filePath, $mtime);

    /**
     * @param string $filePath
     * @param int $position
     */
    public function writePacketFileContinue($filePath, $position);

    /**
     * @param string $chunk
     */
    public function writePacketFileChunk($chunk);

    /**
     * @param string $mode
     */
    public function writePacketFileEnd($mode);

    /**
     * @param string $linkPath
     * @param string $target
     */
    public function writePacketSymlink($linkPath, $target);

    /**
     * @param string $errorCode
     * @param array|null $errorParams
     */
    public function writePacketError($errorCode, $errorParams=null);

    public function writePacketStreamEnd();

    /**
     * @param string $path
     */
    public function writePacketDirectoryListStart($path);

    /**
     * @param string[] $items
     */
    public function writePacketDirectoryListChunk($items);

    public function writePacketDirectoryListEnd();
}
