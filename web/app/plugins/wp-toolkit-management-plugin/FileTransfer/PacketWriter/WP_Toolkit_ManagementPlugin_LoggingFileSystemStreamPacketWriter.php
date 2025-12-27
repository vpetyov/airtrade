<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketWriter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggingFileSystemStreamPacketWriter implements FileSystemStreamPacketWriterInterface
{
    /**
     * @var FileSystemStreamPacketWriterInterface
     */
    private $fileSystemStreamPacketWriter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param FileSystemStreamPacketWriterInterface $fileSystemStreamPacketWriter
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        FileSystemStreamPacketWriterInterface $fileSystemStreamPacketWriter,
        ?LoggerInterface $logger = null
    )
    {
        $this->fileSystemStreamPacketWriter = $fileSystemStreamPacketWriter;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param string $directoryPath
     * @param string $mtime
     */
    public function writePacketDirectory($directoryPath, $mtime)
    {
        $this->logger->debug("Stream packet: DIRECTORY directoryPath='{$directoryPath}'");
        $this->fileSystemStreamPacketWriter->writePacketDirectory($directoryPath, $mtime);
    }

    /**
     * @param string $directoryPath
     * @param string $mode
     */
    public function writePacketDirectoryEnd($directoryPath, $mode)
    {
        $this->logger->debug("Stream packet: DIRECTORY END directoryPath='{$directoryPath}' mode={$mode}");
        $this->fileSystemStreamPacketWriter->writePacketDirectoryEnd($directoryPath, $mode);
    }

    /**
     * @param string $filePath
     * @param string $mtime
     */
    public function writePacketFileStart($filePath, $mtime)
    {
        $this->logger->debug("Stream packet: FILE START filePath='{$filePath}'");
        $this->fileSystemStreamPacketWriter->writePacketFileStart($filePath, $mtime);
    }

    /**
     * @param string $filePath
     * @param int $position
     */
    public function writePacketFileContinue($filePath, $position)
    {
        $this->logger->debug("Stream packet: FILE CONTINUE filePath='{$filePath}' position='{$position}'");
        $this->fileSystemStreamPacketWriter->writePacketFileContinue($filePath, $position);
    }

    /**
     * @param string $chunk
     */
    public function writePacketFileChunk($chunk)
    {
        $chunkLength = strlen($chunk);
        $this->logger->debug("Stream packet: FILE CHUNK of {$chunkLength} bytes");
        $this->fileSystemStreamPacketWriter->writePacketFileChunk($chunk);
    }

    /**
     * @param string $mode
     */
    public function writePacketFileEnd($mode)
    {
        $this->logger->debug("Stream packet: FILE END");
        $this->fileSystemStreamPacketWriter->writePacketFileEnd($mode);
    }

    /**
     * @param string $linkPath
     * @param string $target
     */
    public function writePacketSymlink($linkPath, $target)
    {
        $this->logger->debug("Stream packet: SYMLINK linkPath='{$linkPath}' target='{$target}'");
        $this->fileSystemStreamPacketWriter->writePacketSymlink($linkPath, $target);
    }

    /**
     * @param string $errorCode
     * @param array|null $errorParams
     */
    public function writePacketError($errorCode, $errorParams=null)
    {
        $errorParamsStr = var_export($errorParams, true);
        $this->logger->debug("Stream packet: ERROR errorCode='{$errorCode}' errorParams={$errorParamsStr}");
        $this->fileSystemStreamPacketWriter->writePacketError($errorCode, $errorParams);
    }

    public function writePacketStreamEnd()
    {
        $this->logger->debug('Stream packet: STREAM END');
        $this->fileSystemStreamPacketWriter->writePacketStreamEnd();
    }

    /**
     * @param string $path
     */
    public function writePacketDirectoryListStart($path)
    {
        $this->logger->debug("Stream packet: DIRECTORY LIST START path='{$path}'");
        $this->fileSystemStreamPacketWriter->writePacketDirectoryListStart($path);
    }

    /**
     * @param string[] $items
     */
    public function writePacketDirectoryListChunk($items)
    {
        $chunkLength = count($items);
        $textItems = implode(PHP_EOL, $items);
        $this->logger->debug("Stream packet: DIRECTORY LIST CHUNK length='{$chunkLength}'" . PHP_EOL . $textItems);
        $this->fileSystemStreamPacketWriter->writePacketDirectoryListChunk($items);
    }

    public function writePacketDirectoryListEnd()
    {
        $this->logger->debug("Stream packet: DIRECTORY LIST END");
        $this->fileSystemStreamPacketWriter->writePacketDirectoryListEnd();
    }
}
