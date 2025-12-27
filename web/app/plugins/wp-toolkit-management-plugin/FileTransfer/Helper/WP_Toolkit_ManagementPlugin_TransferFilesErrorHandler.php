<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Helper;

use PleskExt\WpToolkit\FileTransfer\ByteStream\StringByteStream;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\ByteStreamPacketReader;
use PleskExt\WpToolkit\Service\I18n\TranslatorInterface;
use Psr\Log\LoggerInterface;

class TransferFilesErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @param string $output
     * @return string[]
     */
    public function handleErrorMessagesFromTransferFilesOutput(string $output): array
    {
        $errors = [];
        $resultStream = new StringByteStream($output);
        $resultPacketReader = new ByteStreamPacketReader($resultStream);
        while (true) {
            $packet = $resultPacketReader->nextPacket();
            if (is_null($packet)) {
                break;
            }
            if ($packet instanceof ErrorPacket) {
                $errorCode = $packet->getCode();
                $errorParameters = $packet->getParams();

                $errorMessage = $this->handleErrorMessage($errorCode, $errorParameters);
                if (!is_null($errorMessage)) {
                    $errors[] = $errorMessage;
                }
            }
        }

        return $errors;
    }

    /**
     * Handle an error message reported by transfer files process and decide whether:
     * 1. Report to customer (errors like that are returned).
     * 2. Put into debug log (for warnings and notices which are interesting for debugging purposes only).
     * 3. Stop transfer execution by throwing an exception (in case of fatal error).
     *
     * @param string $errorCode
     * @param array|null $errorParameters
     * @return string|null
     * @throws \Exception
     */
    private function handleErrorMessage(string $errorCode, ?array $errorParameters): ?string
    {
        $errorMessage = null;
        $errorParameters = is_array($errorParameters) ? $errorParameters : [];
        if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_DIR_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadDir', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_DIR_PARAM_DIRECTORY],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToGetListOfTargetDir', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_PARAM_PATH],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_OPEN_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToOpenFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_OPEN_FILE_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadFileContentsFull', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadFileContentsPartial', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_FILE],
                'bytes' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_BYTES],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_SYMLINK_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadSymlink', [
                'symlink' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_SYMLINK_PARAM_SYMLINK],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_FILE_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadFileMode', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_FILE_MODE_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadDirMode', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToGetChangeDirMode', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_CHANGE_DIR_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToChangeDirMode', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_CHANGE_DIR_MODE_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToRemoveDir', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToRemoveFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_CREATE_DIR_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToCreateDir', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_CREATE_DIR_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_SET_DIR_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToSetDirMode', [
                'directory' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SET_DIR_MODE_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_SEEK_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToSeekFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SEEK_FILE_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToWriteFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_PARAM_FILE],
                'originalFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_PARAM_ORIGINAL_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToSetFileMode', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_PARAM_FILE],
                'originalFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_PARAM_ORIGINAL_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_SET_FILE_MTIME_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToSetFileMtime', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SET_FILE_MTIME_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_SET_DIR_MTIME_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToSetDirMtime', [
                'dir' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_SET_DIR_MTIME_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadMtimeOnSourceFileSkipped', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadMtimeOnSourceDirSkipped', [
                'dir' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadMtimeOnSource', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadDirMtimeOnSource', [
                'dir' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadMtimeOnTargetFileSkipped', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED_PARAM_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReadMtimeOnTargetDirSkipped', [
                'dir' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED_PARAM_DIR],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToReplaceLocalFile', [
                'localFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_LOCAL_FILE],
                'temporaryFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_TEMPORARY_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToOpenWriteFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_FILE],
                'originalFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_ORIGINAL_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToCloseFile', [
                'file' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_PARAM_FILE],
                'originalFile' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_PARAM_ORIGINAL_FILE],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToRemoveSymlink', [
                'symlink' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_PARAM_SYMLINK],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_FAILED_TO_CREATE_SYMLINK_CODE) {
            $errorMessage = $this->translator->translate('fileTransfer.failedToCreateSymlink', [
                'symlink' => $errorParameters[ErrorPacket::ERROR_FAILED_TO_CREATE_SYMLINK_PARAM_SYMLINK],
            ]);
        } else if ($errorCode === ErrorPacket::ERROR_READER_WARNINGS_CODE) {
            $readerCommand = $errorParameters[ErrorPacket::ERROR_READER_WARNINGS_PARAM_COMMAND];
            $readerOutput = $errorParameters[ErrorPacket::ERROR_READER_WARNINGS_PARAM_OUTPUT];
            $this->logger->warning(
                "Reader process of transfer files returned unexpected output.\n" .
                "Command: {$readerCommand}\n" .
                "Unexpected output (stdout): {$readerOutput}"
            );
        } else if ($errorCode === ErrorPacket::ERROR_WRITER_WARNINGS_CODE) {
            $writerCommand = $errorParameters[ErrorPacket::ERROR_WRITER_WARNINGS_PARAM_COMMAND];
            $writerOutput = $errorParameters[ErrorPacket::ERROR_WRITER_WARNINGS_PARAM_OUTPUT];
            $this->logger->warning(
                "Writer process of transfer files returned unexpected output.\n" .
                "Command: {$writerCommand}\n" .
                "Unexpected output (stdout): {$writerOutput}"
            );
        } else if ($errorCode === ErrorPacket::ERROR_READER_FATAL_ERROR_CODE) {
            throw new \Exception(
                $this->translator->translate(
                    'fileTransfer.error.readerFatalError', [
                        'command' => $errorParameters[ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_COMMAND],
                        'code' => $errorParameters[ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_EXIT_CODE],
                        'stderr' => $errorParameters[ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_OUTPUT],
                    ]
                )
            );
        } else if ($errorCode === ErrorPacket::ERROR_WRITER_FATAL_ERROR_CODE) {
            throw new \Exception(
                $this->translator->translate(
                    'fileTransfer.error.writerFatalError', [
                        'command' => $errorParameters[ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_COMMAND],
                        'code' => $errorParameters[ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_EXIT_CODE],
                        'stderr' => $errorParameters[ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_OUTPUT],
                    ]
                )
            );
        } else {
            $errorMessage = $this->translator->translate('fileTransfer.error.general', [
                'code' => $errorCode
            ]);
        }

        return $errorMessage;
    }
}
