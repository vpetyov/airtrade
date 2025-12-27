<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketHandlers;

use PleskExt\WpToolkit\FileTransfer\Helper\UnixPermissionsHelper;
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

class LocalFileWritePacketHandler implements PacketHandler
{
    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @var string[] Dir modification time
     */
    private $originalDirMtimeMap = array();

    /**
     * @var resource|null
     */
    private $currentFp;

    /**
     * @var string|null
     */
    private $originalFilePath;

    /**
     * @var string|null
     */
    private $temporaryFilePath;

    /**
     * @var string|null File modification time
     */
    private $originalFileMtime;

    /**
     * @var bool|null
     */
    private $skipPacketsForCurrentFile;

    /**
     * @var PacketHandler
     */
    private $errorPacketHandler;

    /**
     * @var bool
     */
    private $transferPermissions;

    /**
     * @var array
     */
    private $directoryInitialModes;

    /**
     * @var string
     */
    private $skippedDirectory;

    /**
     * @var bool
     */
    private $replaceModified;

    /**
     * @var string
     */
    private $directoryListPath = null;

    /**
     * @var string[]
     */
    private $directoryList = [];

    /**
     * @param string $destinationPath
     * @param PacketHandler|null $errorPacketHandler
     * @param bool $transferPermissions
     * @param bool $replaceModified
     */
    public function __construct(
        string $destinationPath,
        ?PacketHandler $errorPacketHandler = null,
        bool $transferPermissions = true,
        bool $replaceModified = true
    ) {
        $this->destinationPath = $destinationPath;
        $this->errorPacketHandler = $errorPacketHandler;
        $this->transferPermissions = $transferPermissions;
        $this->replaceModified = $replaceModified;

        $this->currentFp = null;
        $this->originalFilePath = null;
        $this->temporaryFilePath = null;
        $this->skippedDirectory = null;
        $this->directoryInitialModes = [];
    }

    public function onDirectoryPacket(DirectoryPacket $packet)
    {
        if (!is_null($this->skippedDirectory)) {
            return;
        }

        $absPath = $this->getAbsPath($packet->getDirectoryPath());

        // Base permissions we need for transfer of files inside of the directory to happen.
        $baseTransferPermissions = 0700;

        // Default permissions we set for directory if transfer file permissions is disabled
        $defaultPermissions = 0755;

        $originalDirMtime = null;
        if (!empty($packet->getMtime())) {
            $originalDirMtime = $packet->getMtime();
        } else {
            if (!$this->replaceModified && file_exists($absPath)) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_DIR_SKIPPED_PARAM_DIR => $absPath
                    )
                );
                $this->skippedDirectory = $packet->getDirectoryPath();
                return;
            } else {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_DIR_MTIME_ON_SOURCE_PARAM_DIR => $absPath
                    )
                );
            }
        }

        if (!$this->replaceModified && !is_null($originalDirMtime) && file_exists($absPath)) {
            /**
             * The mtime (modification time) on the directory itself changes when a file
             * or a subdirectory is added, removed or renamed.
             * Modifying the contents of a file within the directory does not change the directory itself,
             * nor does updating the modified times of a file or a subdirectory
             */
            $localMtime = $this->getItemMtime($absPath);

            if ($localMtime === false) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_DIR_SKIPPED_PARAM_DIR => $absPath
                    )
                );
                $this->skippedDirectory = $packet->getDirectoryPath();
                return;
            }

            if ($localMtime >= $originalDirMtime && !@is_dir($absPath)) {
                $this->skippedDirectory = $packet->getDirectoryPath();
                return;
            }
        }

        if (@file_exists($absPath) && @is_dir($absPath)) {
            if (!is_null($originalDirMtime)) {
                $this->originalDirMtimeMap[$absPath] = $originalDirMtime;
            }

            $mode = UnixPermissionsHelper::getModeAsNumber($absPath);
            if ($mode !== false) {
                $transferMode = $mode | $baseTransferPermissions;
                if ($transferMode !== $mode) {
                    $this->directoryInitialModes[$packet->getDirectoryPath()] = $mode;
                    $isChangeModeSuccess = @chmod($absPath, $transferMode);
                    if (!$isChangeModeSuccess) {
                        // Report error, but still continue execution - may be we're lucky and everything will go well
                        $this->reportError(
                            ErrorPacket::ERROR_FAILED_TO_CHANGE_DIR_MODE_CODE, array(
                                ErrorPacket::ERROR_FAILED_TO_CHANGE_DIR_MODE_PARAM_DIR => $absPath
                            )
                        );
                    }
                }
            } else {
                // Report error, but still continue execution - may be we're lucky and everything will go well
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_GET_CHANGE_DIR_MODE_PARAM_DIR => $absPath
                    )
                );
            }
        } else {
            if (@file_exists($absPath) && !@is_dir($absPath)) {
                $isRemoveSuccess = $this->removeFilePath($absPath);
                if (!$isRemoveSuccess) {
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_PARAM_DIR => $absPath
                        )
                    );
                    // There is something (file, symlink, etc) instead of the directory,
                    // and we failed to remove it - it does not make sense to continue with
                    // that directory
                    $this->skippedDirectory = $packet->getDirectoryPath();
                    return;
                }
            }

            $isCreateDirSuccess = @mkdir(
                $absPath,
                $this->transferPermissions ? $baseTransferPermissions : $defaultPermissions,
                true
            );

            if (!$isCreateDirSuccess) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_CREATE_DIR_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_CREATE_DIR_PARAM_DIR => $absPath
                    )
                );
                $this->skippedDirectory = $packet->getDirectoryPath();
                return;
            } elseif (!is_null($originalDirMtime)) {
                $this->originalDirMtimeMap[$absPath] = $originalDirMtime;
            }
        }
    }

    public function onDirectoryEndPacket(DirectoryEndPacket $packet)
    {
        if ($this->skippedDirectory) {
            if ($this->skippedDirectory === $packet->getDirectoryPath()) {
                $this->skippedDirectory = null;
                return;
            }
            return;
        }

        $isSetModeSuccess = true;
        $absPath = $this->getAbsPath($packet->getDirectoryPath());

        if (!$this->transferPermissions) {
            if (isset($this->directoryInitialModes[$packet->getDirectoryPath()])) {
                $initialMode = $this->directoryInitialModes[$packet->getDirectoryPath()];
                $isSetModeSuccess = @chmod($absPath, $initialMode);
                unset($this->directoryInitialModes[$packet->getDirectoryPath()]);
            }
        } else {
            if ($packet->getMode() !== '') {
                $isSetModeSuccess = @chmod(
                    $absPath,
                    UnixPermissionsHelper::stringToOctalNumberMode($packet->getMode())
                );
            }
        }

        if (!$isSetModeSuccess) {
            $this->reportError(
                ErrorPacket::ERROR_FAILED_TO_SET_DIR_MODE_CODE, array(
                    ErrorPacket::ERROR_FAILED_TO_SET_DIR_MODE_PARAM_DIR => $absPath
                )
            );
        }

        if (isset($this->originalDirMtimeMap[$absPath])) {
            $isTimeChangeSuccess = touch($absPath, $this->originalDirMtimeMap[$absPath]);
            unset($this->originalDirMtimeMap[$absPath]);
            if (!$isTimeChangeSuccess) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_SET_DIR_MTIME_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_SET_DIR_MTIME_PARAM_DIR => $absPath
                    )
                );
            }
        }
    }

    public function onFileStartPacket(FileStartPacket $packet)
    {
        if (!is_null($this->skippedDirectory)) {
            return;
        }

        $this->closeCurrentFp();
        $fullFilepath = $this->getAbsPath($packet->getFilePath());

        if (!empty($packet->getMtime())) {
            $this->originalFileMtime = $packet->getMtime();
        } else {
            if (!$this->replaceModified && file_exists($fullFilepath)) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_FILE_SKIPPED_PARAM_FILE => $fullFilepath
                    )
                );
                $this->skipPacketsForCurrentFile = true;
                return;
            } else {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_SOURCE_PARAM_FILE => $fullFilepath
                    )
                );
            }
        }

        if (!is_null($this->originalFileMtime) && !$this->replaceModified && file_exists($fullFilepath)) {
            $localMtime = $this->getItemMtime($fullFilepath);
            if ($localMtime === false) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED, array(
                        ErrorPacket::ERROR_FAILED_TO_READ_MTIME_ON_TARGET_FILE_SKIPPED_PARAM_FILE => $fullFilepath
                    )
                );
                $this->skipPacketsForCurrentFile = true;
                $this->originalFileMtime = null;
                return;
            } elseif ($localMtime >= $this->originalFileMtime) {
                $this->skipPacketsForCurrentFile = true;
                return;
            }
        }

        $this->openFileForWrite($fullFilepath);
    }

    public function onFileContinuePacket(FileContinuePacket $packet)
    {
        throw new \Exception("Handling of 'continue' packets is not implemented");
    }

    public function onFileChunkPacket(FileChunkPacket $packet)
    {
        if ($this->skipPacketsForCurrentFile) {
            return;
        }
        if (!is_null($this->currentFp)) {
            $isWriteSuccess = @fwrite($this->currentFp, $packet->getData());
            if (!$isWriteSuccess) {
                $temporaryFilePath = $this->temporaryFilePath;
                $this->removeFilePath($temporaryFilePath);
                $this->resetCurrentFile();
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_PARAM_FILE => $temporaryFilePath,
                        ErrorPacket::ERROR_FAILED_TO_WRITE_FILE_PARAM_ORIGINAL_FILE => $this->originalFilePath,
                    )
                );
            }
        }
    }

    public function onFileEndPacket(FileEndPacket $packet)
    {
        if (!is_null($this->skippedDirectory)) {
            return;
        }

        if ($this->skipPacketsForCurrentFile) {
            $this->resetCurrentFile();
            return;
        }
        $originalFilePath = $this->originalFilePath;
        $temporaryFilePath = $this->temporaryFilePath;
        $originalFileMtime = $this->originalFileMtime;
        $this->closeCurrentFp();
        if ($this->transferPermissions && $temporaryFilePath && $packet->getMode() !== '') {
            $isSetModeSuccess = @chmod(
                $temporaryFilePath,
                UnixPermissionsHelper::stringToOctalNumberMode($packet->getMode())
            );
            if (!$isSetModeSuccess) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_PARAM_FILE => $temporaryFilePath,
                        ErrorPacket::ERROR_FAILED_TO_SET_FILE_MODE_PARAM_ORIGINAL_FILE => $originalFilePath,
                    )
                );
            }
        }
        if (!is_null($temporaryFilePath)) {
            if (@file_exists($originalFilePath) && !@is_file($originalFilePath)) {
                $isRemoveSuccess = $this->removeFilePath($originalFilePath);
                if (!$isRemoveSuccess) {
                    $this->removeFilePath($temporaryFilePath);
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_PARAM_FILE => $originalFilePath
                        )
                    );
                    return;
                }
            }

            $isMoveSuccess = @rename($temporaryFilePath, $originalFilePath);
            if (!$isMoveSuccess) {
                $this->removeFilePath($temporaryFilePath);
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE, array(
                        ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_LOCAL_FILE => $originalFilePath,
                        ErrorPacket::ERROR_FAILED_TO_REPLACE_LOCAL_FILE_PARAM_TEMPORARY_FILE => $temporaryFilePath,
                    )
                );
                return;
            }
        }
        if ($originalFilePath && !is_null($originalFileMtime)) {
            $isTimeChangeSuccess = touch($originalFilePath, $originalFileMtime);
            if (!$isTimeChangeSuccess) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_SET_FILE_MTIME_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_SET_FILE_MTIME_PARAM_FILE => $originalFilePath
                    )
                );
            }
        }
    }

    public function onSymlinkPacket(SymlinkPacket $packet)
    {
        if (!is_null($this->skippedDirectory)) {
            return;
        }

        if (!is_null($packet->getTarget()) && $packet->getTarget() !== ''
            && !is_null($packet->getSymlinkPath()) && $packet->getSymlinkPath() !== ''
        ) {
            $targetPath = escapeshellarg($packet->getTarget());
            $symlinkPath = escapeshellarg($this->getAbsPath($packet->getSymlinkPath()));

            if (@file_exists($symlinkPath)) {
                $isRemoveSuccess = $this->removeFilePath($symlinkPath);
                if (!$isRemoveSuccess) {
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_PARAM_SYMLINK => $symlinkPath,
                        )
                    );
                }
            }

            exec("ln -f -s $targetPath $symlinkPath", $output, $returnCode);
            if ($returnCode !== 0) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_CREATE_SYMLINK_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_CREATE_SYMLINK_PARAM_SYMLINK => $symlinkPath,
                    )
                );
            }
        }
    }

    public function onErrorPacket(ErrorPacket $packet)
    {
        if (!is_null($this->errorPacketHandler)) {
            $this->errorPacketHandler->onErrorPacket($packet);
        }
    }

    public function onStreamEnd()
    {
        $this->closeCurrentFp();
    }


    public function onDirectoryListStartPacket(DirectoryListStartPacket $packet)
    {
        $this->directoryListPath = $packet->getDirectoryPath();
    }


    public function onDirectoryListChunkPacket(DirectoryListChunkPacket $packet)
    {
        $this->directoryList = array_merge($this->directoryList, $packet->getDirectoryListItems());
    }

    public function onDirectoryListEndPacket()
    {
        $localDirectoryAbsPath = $this->getAbsPath($this->directoryListPath);
        $localDirectoryList = @scandir($localDirectoryAbsPath);
        if ($localDirectoryList === false) {
            $this->reportError(ErrorPacket::ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_CODE,
                array(
                    ErrorPacket::ERROR_FAILED_TO_GET_DIRECTORY_LIST_ON_TARGET_PARAM_PATH => $localDirectoryAbsPath,
                )
            );

            $this->directoryListPath = null;
            $this->directoryList = [];
            return;
        }

        $localDirectoryList = array_diff($localDirectoryList, ['.', '..']);
        $itemsToRemove = array_diff($localDirectoryList, $this->directoryList);
        foreach ($itemsToRemove as $item) {
            $itemAbsPath = $localDirectoryAbsPath . DIRECTORY_SEPARATOR . $item;
            $isRemoveSuccess = $this->removeFilePath($itemAbsPath);
            if (!$isRemoveSuccess) {
                if (is_dir($itemAbsPath)) {
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_DIR_PARAM_DIR => $itemAbsPath
                        )
                    );
                } elseif (is_link($itemAbsPath)) {
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_SYMLINK_PARAM_SYMLINK => $itemAbsPath
                        )
                    );
                } elseif (is_file($itemAbsPath)) {
                    $this->reportError(
                        ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_CODE, array(
                            ErrorPacket::ERROR_FAILED_TO_REMOVE_FILE_PARAM_FILE => $itemAbsPath
                        )
                    );
                }
            }
        }

        $this->directoryListPath = null;
        $this->directoryList = [];
    }

    /**
     * @param string $path
     */
    private function openFileForWrite($path)
    {
        $temporaryFilePath = $this->getTemporaryFilePath($path);
        $fp = fopen($temporaryFilePath, 'w');
        if ($fp !== false) {
            $this->setCurrentFile($fp, $path, $temporaryFilePath);
        } else {
            $this->reportError(
                ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_CODE, array(
                    ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_FILE => $temporaryFilePath,
                    ErrorPacket::ERROR_FAILED_TO_OPEN_WRITE_FILE_PARAM_ORIGINAL_FILE => $path,
                )
            );
            $this->resetCurrentFile();
        }
    }

    private function closeCurrentFp()
    {
        if (!is_null($this->currentFp)) {
            $isCloseSuccess = fclose($this->currentFp);
            if (!$isCloseSuccess) {
                $this->reportError(
                    ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_CODE, array(
                        ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_PARAM_FILE => $this->temporaryFilePath,
                        ErrorPacket::ERROR_FAILED_TO_CLOSE_FILE_PARAM_ORIGINAL_FILE => $this->originalFilePath,
                    )
                );
            }
            $this->resetCurrentFile();
        }
    }

    /**
     * @param resource $fp
     * @param string $originalFilePath
     * @param string $temporaryFilePath
     */
    private function setCurrentFile($fp, $originalFilePath, $temporaryFilePath)
    {
        $this->currentFp = $fp;
        $this->originalFilePath = $originalFilePath;
        $this->temporaryFilePath = $temporaryFilePath;
    }

    private function resetCurrentFile()
    {
        $this->currentFp = null;
        $this->originalFilePath = null;
        $this->temporaryFilePath = null;
        $this->originalFileMtime = null;
        $this->skipPacketsForCurrentFile = null;
    }

    /**
     * @param string|null $itemPath
     * @return string
     */
    private function getAbsPath($itemPath)
    {
        if (!is_null($itemPath) && $itemPath !== '') {
            return $this->destinationPath . DIRECTORY_SEPARATOR . $itemPath;
        } else {
            return $this->destinationPath;
        }
    }

    /**
     * @param string $path absolute path you want to remove
     * @return bool
     */
    private function removeFilePath($path)
    {
        $isSuccess = true;

        if (is_dir($path)) {
            $directoryIterator = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    if (!@rmdir($file->getPathname())) {
                        $isSuccess = false;
                    }
                } else {
                    if (!@unlink($file->getPathname())) {
                        $isSuccess = false;
                    }
                }
            }
            if (!@rmdir($path)) {
                $isSuccess = false;
            }
        } else {
            $isSuccess = @unlink($path);
        }
        return $isSuccess;
    }

    /**
     * @param string $code see \PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket::ERROR_*_CODE
     * @param array|null $params see \PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket::ERROR_*_PARAM_*
     */
    private function reportError($code, $params=null)
    {
        if (!is_null($this->errorPacketHandler)) {
            $errorPacket = new ErrorPacket($code, $params);
            $this->errorPacketHandler->onErrorPacket($errorPacket);
        }
    }

    /**
     * @param string $originalFilePath
     * @return string Will return new value for each call
     */
    private function getTemporaryFilePath($originalFilePath)
    {
        $directoryPath = rtrim(dirname($originalFilePath), '/\\') . '/';
        $originalFilename = basename($originalFilePath);
        $originalFileExt = pathinfo($originalFilePath, PATHINFO_EXTENSION);
        $tempFilename = uniqid(".{$originalFilename}.temp.");
        return "{$directoryPath}{$tempFilename}.{$originalFileExt}";
    }

    /**
     * Get modification time of file/directory/symlink. Symlinks are not followed - you get modification time
     * of symlink itself, not modification time of referenced file/directory.
     *
     * @param $path
     * @return bool|int
     */
    private function getItemMtime($path)
    {
        if (@is_link($path)) {
            $stat = @lstat($path);
            if ($stat === false) {
                return false;
            }
            return $stat['mtime'];
        } else {
            return filemtime($path);
        }
    }
}
