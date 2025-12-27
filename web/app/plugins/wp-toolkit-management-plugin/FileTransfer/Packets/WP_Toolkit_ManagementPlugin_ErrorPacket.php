<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Packets;

class ErrorPacket
{
    const ERROR_FAILED_TO_READ_DIR_CODE = 'failedToReadDir';
    const ERROR_FAILED_TO_READ_DIR_PARAM_DIRECTORY = 'failedToReadDir.directory';
    const ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_CODE = 'failedToGetTargetDirList';
    const ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_PARAM_PATH = 'failedToGetTargetDirList.path';
    const ERROR_FAILED_TO_OPEN_FILE_CODE = 'failedToOpenFile';
    const ERROR_FAILED_TO_OPEN_FILE_PARAM_FILE = 'failedToOpenFile.file';
    const ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_CODE = 'failedToReadFileContentsFull';
    const ERROR_FAILED_TO_READ_FILE_CONTENTS_FULL_PARAM_FILE = 'failedToReadFileContentsFull.file';
    const ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_CODE = 'failedToReadFileContentsPartial';
    const ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_FILE = 'failedToReadFileContentsPartial.file';
    const ERROR_FAILED_TO_READ_FILE_CONTENTS_PARTIAL_PARAM_BYTES = 'failedToReadFileContentsPartial.bytes';
    const ERROR_FAILED_TO_READ_SYMLINK_CODE = 'failedToReadSymlink';
    const ERROR_FAILED_TO_READ_SYMLINK_PARAM_SYMLINK = 'failedToReadSymlink.symlink';
    const ERROR_FAILED_TO_READ_FILE_MODE_CODE = 'failedToReadFileMode';
    const ERROR_FAILED_TO_READ_FILE_MODE_PARAM_FILE = 'failedToReadFileMode.file';
    const ERROR_FAILED_TO_READ_DIR_MODE_CODE = 'failedToReadDirMode';
    const ERROR_FAILED_TO_READ_DIR_MODE_PARAM_DIR = 'failedToReadDirMode.dir';
    const ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_CODE = 'failedToGetChangeDirMode';
    const ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_PARAM_DIR = 'failedToGetChangeDirMode.dir';
    const ERROR_FAILED_TO_CHANGE_DIR_MODE_CODE = 'failedToChangeDirMode';
    const ERROR_FAILED_TO_CHANGE_DIR_MODE_PARAM_DIR = 'failedToChangeDirMode.dir';
    const ERROR_FAILED_TO_REMOVE_DIR_CODE = 'failedToRemoveDir';
    const ERROR_FAILED_TO_REMOVE_DIR_PARAM_DIR = 'failedToRemoveDir.dir';
    const ERROR_FAILED_TO_REMOVE_FILE_CODE = 'failedToRemoveFile';
    const ERROR_FAILED_TO_REMOVE_FILE_PARAM_FILE = 'failedToRemoveFile.file';
    const ERROR_FAILED_TO_CREATE_DIR_CODE = 'failedToCreateDir';
    const ERROR_FAILED_TO_CREATE_DIR_PARAM_DIR = 'failedToCreateDir.dir';
    const ERROR_FAILED_TO_SET_DIR_MODE_CODE = 'failedToSetDirMode';
    const ERROR_FAILED_TO_SET_DIR_MODE_PARAM_DIR = 'failedToSetDirMode.dir';
    const ERROR_FAILED_TO_SEEK_FILE_CODE = 'failedToSeekFile';
    const ERROR_FAILED_TO_SEEK_FILE_PARAM_FILE = 'failedToSeekFile.file';
    const ERROR_FAILED_TO_WRITE_FILE_CODE = 'failedToWriteFile';
    const ERROR_FAILED_TO_WRITE_FILE_PARAM_FILE = 'failedToWriteFile.file';
    const ERROR_FAILED_TO_WRITE_FILE_PARAM_ORIGINAL_FILE = 'failedToWriteFile.originalFile';
    const ERROR_FAILED_TO_SET_FILE_MODE_CODE = 'failedToSetFileMode';
    const ERROR_FAILED_TO_SET_FILE_MODE_PARAM_FILE = 'failedToSetFileMode.file';
    const ERROR_FAILED_TO_SET_FILE_MODE_PARAM_ORIGINAL_FILE = 'failedToSetFileMode.originalFile';
    const ERROR_FAILED_TO_SET_FILE_MTIME_CODE = 'failedToSetFileMtime';
    const ERROR_FAILED_TO_SET_FILE_MTIME_PARAM_FILE = 'failedToSetFileMtime.file';
    const ERROR_FAILED_TO_SET_DIR_MTIME_CODE = 'failedToSetDirMtime';
    const ERROR_FAILED_TO_SET_DIR_MTIME_PARAM_DIR = 'failedToSetDirMtime.dir';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED = 'failedToReadMtimeOnSourceFileSkipped';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED_PARAM_FILE = 'failedToReadMtimeOnSourceFileSkipped.file';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED = 'failedToReadMtimeOnSourceDirSkipped';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED_PARAM_DIR = 'failedToReadMtimeOnSourceDirSkipped.dir';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE = 'failedToReadMtimeOnSource';
    const ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_PARAM_FILE = 'failedToReadMtimeOnSource.file';
    const ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE = 'failedToReadDirMtimeOnSource';
    const ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE_PARAM_DIR = 'failedToReadDirMtimeOnSource.dir';
    const ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED = 'failedToReadMtimeOnTargetFileSkipped';
    const ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED_PARAM_FILE = 'failedToReadMtimeOnTargetFileSkipped.file';
    const ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED = 'failedToReadMtimeOnTargetDirSkipped';
    const ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED_PARAM_DIR = 'failedToReadMtimeOnTargetDirSkipped.dir';
    const ERROR_FAILED_TO_REPLACE_LOCAL_FILE = 'failedToReplaceLocalFile';
    const ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_LOCAL_FILE = 'failedToReplaceLocalFile.localFile';
    const ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_TEMPORARY_FILE = 'failedToReplaceLocalFile.temporaryFile';
    const ERROR_FAILED_TO_OPEN_WRITE_FILE_CODE = 'failedToOpenWriteFile';
    const ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_FILE = 'failedToOpenWriteFile.file';
    const ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_ORIGINAL_FILE = 'failedToOpenWriteFile.originalFile';
    const ERROR_FAILED_TO_CLOSE_FILE_CODE = 'failedToCloseFile';
    const ERROR_FAILED_TO_CLOSE_FILE_PARAM_FILE = 'failedToCloseFile.file';
    const ERROR_FAILED_TO_CLOSE_FILE_PARAM_ORIGINAL_FILE = 'failedToCloseFile.originalFile';
    const ERROR_FAILED_TO_REMOVE_SYMLINK_CODE = 'failedToRemoveSymlink';
    const ERROR_FAILED_TO_REMOVE_SYMLINK_PARAM_SYMLINK = 'failedToRemoveSymlink.symlink';
    const ERROR_FAILED_TO_CREATE_SYMLINK_CODE = 'failedToCreateSymlink';
    const ERROR_FAILED_TO_CREATE_SYMLINK_PARAM_SYMLINK = 'failedToCreateSymlink.symlink';
    const ERROR_READER_FATAL_ERROR_CODE = 'readerFatalError';
    const ERROR_READER_FATAL_ERROR_PARAM_COMMAND = 'readerFatalError.command';
    const ERROR_READER_FATAL_ERROR_PARAM_OUTPUT = 'readerFatalError.output';
    const ERROR_READER_FATAL_ERROR_PARAM_EXIT_CODE = 'readerFatalError.exitCode';
    const ERROR_READER_WARNINGS_CODE = 'readerWarnings';
    const ERROR_READER_WARNINGS_PARAM_COMMAND = 'readerWarnings.command';
    const ERROR_READER_WARNINGS_PARAM_OUTPUT = 'readerWarnings.output';
    const ERROR_WRITER_FATAL_ERROR_CODE = 'writerFatalError';
    const ERROR_WRITER_FATAL_ERROR_PARAM_COMMAND = 'writerFatalError.command';
    const ERROR_WRITER_FATAL_ERROR_PARAM_OUTPUT = 'writerFatalError.output';
    const ERROR_WRITER_FATAL_ERROR_PARAM_EXIT_CODE = 'writerFatalError.exitCode';
    const ERROR_WRITER_WARNINGS_CODE = 'writerWarnings';
    const ERROR_WRITER_WARNINGS_PARAM_COMMAND = 'writerWarnings.command';
    const ERROR_WRITER_WARNINGS_PARAM_OUTPUT = 'writerWarnings.output';


    /**
     * @var string
     */
    private $code;

    /**
     * @var array|null
     */
    private $params;

    /**
     * @param string $code
     * @param array|null $params
     */
    public function __construct($code, $params=null)
    {
        $this->code = $code;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function getParams()
    {
        return $this->params;
    }
}
