<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\Helper\UnixPermissionsHelper;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\DirectoryStreamPacketWriter;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\FileSystemStreamPacketWriterInterface;

define('SIZE_MB', 1024 * 1024);
define('SIZE_LINES', 1024);

/**
 * Object capable of writing files and directories to the stream.
 */
class FileObjectsWriter
{
    /**
     * @var DirectoryStreamPacketWriter
     */
    private $fileSystemStreamPacketWriter;

    /**
     * @var int
     */
    private $fileChunkSize;

    /**
     * @var int
     */
    private $arrayChunkSize;

    /**
     * @param FileSystemStreamPacketWriterInterface $fileSystemStreamPacketWriter
     * @param float|int $fileChunkSize
     * @param float|int $arrayChunkSize
     */
    public function __construct($fileSystemStreamPacketWriter, $fileChunkSize = SIZE_MB, $arrayChunkSize = SIZE_LINES)
    {
        $this->fileSystemStreamPacketWriter = $fileSystemStreamPacketWriter;
        $this->fileChunkSize = $fileChunkSize;
        $this->arrayChunkSize = $arrayChunkSize;
    }

    /**
     * @param string $baseFilePath
     * @param string $relativeFilePath
     * @param int $startPosition
     */
    public function writeFile($baseFilePath, $relativeFilePath, $startPosition = 0)
    {
        if ($relativeFilePath !== null && $relativeFilePath !== '') {
            $fullFilePath = $baseFilePath . DIRECTORY_SEPARATOR . $relativeFilePath;
        } else {
            $fullFilePath = $baseFilePath;
        }

        $fp = @fopen($fullFilePath, 'r');
        $mtime = @filemtime($fullFilePath);
        if ($mtime === false) {
            $mtime = '';
        }

        if ($fp !== false) {
            if ($startPosition == 0) {
                $this->fileSystemStreamPacketWriter->writePacketFileStart($relativeFilePath, $mtime);
            } else {
                $seekResult = @fseek($fp, $startPosition);
                if ($seekResult != -1) {
                    $this->fileSystemStreamPacketWriter->writePacketFileContinue($relativeFilePath, $startPosition);
                } else {
                    $this->fileSystemStreamPacketWriter->writePacketFileStart($relativeFilePath, $mtime);
                }
            }

            $readBytes = 0;
            while (!@feof($fp)) {
                $chunk = @fread($fp, $this->fileChunkSize);
                if ($chunk !== false) {
                    $chunkLen = strlen($chunk);
                    if ($chunkLen > 0) {
                        $this->fileSystemStreamPacketWriter->writePacketFileChunk($chunk);
                        $readBytes += $chunkLen;
                    }
                } else {
                    if ($readBytes > 0) {
                        $this->writeError(
                            ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_CODE,
                            array(
                                ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_FILE => $fullFilePath,
                                ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_BYTES => $readBytes,
                            )
                        );
                    } else {
                        $this->writeError(
                            ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_CODE,
                            array(
                                ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_PARAM_FILE => $fullFilePath,
                            )
                        );
                    }
                    break;
                }
            }
        } else {
            $this->writeError(
                ErrorPacket::ERROR_FAILED_TO_OPEN_FILE_CODE,
                array(
                    ErrorPacket::ERROR_FAILED_TO_OPEN_FILE_PARAM_FILE => $fullFilePath,
                )
            );
        }
        $mode = UnixPermissionsHelper::getModeAsOctalString($fullFilePath);
        if ($mode === false) {
            $this->writeError(
                ErrorPacket::ERROR_FAILED_TO_READ_FILE_MODE_CODE,
                array(
                    ErrorPacket::ERROR_FAILED_TO_READ_FILE_MODE_PARAM_FILE => $fullFilePath,
                )
            );
        }
        $this->fileSystemStreamPacketWriter->writePacketFileEnd($mode === false ? '' : $mode);
    }

    /**
     * @param string $relativeDirPath
     * @param string $absoluteDirPath
     */
    public function writeDirectory($relativeDirPath, $absoluteDirPath)
    {
        $mtime = @filemtime($absoluteDirPath);
        if ($mtime === false) {
            $mtime = '';
        }
        $this->fileSystemStreamPacketWriter->writePacketDirectory($relativeDirPath, $mtime);
    }

    /**
     * @param string $relativeDirPath
     * @param string $mode
     */
    public function writeDirectoryEnd($relativeDirPath, $mode)
    {
        $this->fileSystemStreamPacketWriter->writePacketDirectoryEnd($relativeDirPath, $mode);
    }

    public function writeDirectoryList($relativeDirPath, $directoryItems)
    {
        $itemNumbers = count($directoryItems);

        $this->fileSystemStreamPacketWriter->writePacketDirectoryListStart($relativeDirPath);
        $sentSize = 0;
        while ($sentSize < $itemNumbers) {
            $chunkData = array_slice($directoryItems, $sentSize, $this->arrayChunkSize);
            $this->fileSystemStreamPacketWriter->writePacketDirectoryListChunk($chunkData);
            $sentSize += count($chunkData);
        }
        $this->fileSystemStreamPacketWriter->writePacketDirectoryListEnd();
    }

    /**
     * @param string $relativeLinkPath
     * @param string $target
     */
    public function writeSymlink($relativeLinkPath, $target)
    {
        $this->fileSystemStreamPacketWriter->writePacketSymlink($relativeLinkPath, $target);
    }

    /**
     * @param string $errorCode
     * @param array|null $errorParams
     */
    public function writeError($errorCode, $errorParams=null)
    {
        $this->fileSystemStreamPacketWriter->writePacketError($errorCode, $errorParams);
    }
}
