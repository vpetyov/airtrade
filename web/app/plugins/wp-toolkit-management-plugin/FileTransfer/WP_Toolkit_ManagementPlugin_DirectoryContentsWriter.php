<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\Helper\UnixPermissionsHelper;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;

class DirectoryContentsWriter
{
    /**
     * @var FileObjectsWriter $fileObjectsWriter
     */
    private $fileObjectsWriter;

    /**
     * @param FileObjectsWriter $fileObjectsWriter object capable of writing files and directories to stream
     */
    public function __construct($fileObjectsWriter)
    {
        $this->fileObjectsWriter = $fileObjectsWriter;
    }

    /**
     * Write directory contents to HTTP response as stream.
     *
     * @param string $rootItem
     * @param string[]|null $startPathParts array of directory paths describing a file or a directory from which
     * streaming should be continued, e.g. array("var", "www", "vhosts", "example.com", "config.php") to start
     * streaming from "/var/www/vhosts/example.com/config.php" file
     * @param int|null $startPosition position to start transferring file specified in $startPathParts argument from
     * @param array $excludes list of files/directories, which must not be written to the stream
     * @param string|null $subdirectory directory inside of the root directory, used to walk file tree recursively
     * @param bool $isListDirectoryItems
     */
    public function streamDirectoryContents(
        $rootItem, $startPathParts, $startPosition=null, $excludes=array(), $subdirectory=null, $isListDirectoryItems=false
    ) {
        if ($subdirectory === null && is_file($rootItem)) {
            $this->fileObjectsWriter->writeFile($rootItem, null);
            return;
        }

        if (!is_dir($rootItem)) {
            return;
        }

        if ($subdirectory === null) {
            $fullDirectoryPath = $rootItem;
            $this->fileObjectsWriter->writeDirectory('', $fullDirectoryPath);
        } else {
            $fullDirectoryPath = $rootItem . DIRECTORY_SEPARATOR . $subdirectory;
        }

        $directoryItems = $this->getDirectoryItems($fullDirectoryPath);

        // Important detail here - we sort all found items to divide them into 2 groups:
        // items which were already transferred and items which should be transferred
        sort($directoryItems);

        if (is_array($startPathParts) && count($startPathParts) > 0) {
            $startPathItem = $startPathParts[0];
        } else {
            $startPathItem = null;
        }

        // if start item is not passed - start writing directory items from the beginning,
        // otherwise wait for the start item
        $start = ($startPathItem === null);

        if ($isListDirectoryItems) {
            if ($subdirectory === null) {
                $this->fileObjectsWriter->writeDirectoryList('', $directoryItems);
            } else {
                $this->fileObjectsWriter->writeDirectoryList($subdirectory, $directoryItems);
            }
        }

        foreach ($directoryItems as $directoryItem) {
            // if start item is found - write the start item and all directory items after the start item
            if (!$start && $directoryItem === $startPathItem) {
                $start = true;
            }

            if (!$start) {
                continue;
            }

            // Full path of the item on filesystem, to be used with filesystem functions like "fopen", "opendir", etc
            $fullItemPath = $fullDirectoryPath. DIRECTORY_SEPARATOR . $directoryItem;

            // Relative path of the item, related to the $rootDirectory. Relative path is written to the stream.
            if ($subdirectory === null) {
                $relativePath = $directoryItem;
            } else {
                $relativePath = $subdirectory . DIRECTORY_SEPARATOR . $directoryItem;
            }

            if (in_array($relativePath, $excludes)) {
                continue;
            }

            if (@is_link($fullItemPath)) {
                $target = @readlink($fullItemPath);
                if ($target !== false) {
                    $this->fileObjectsWriter->writeSymlink($relativePath, $target);
                } else {
                    $this->fileObjectsWriter->writeError(
                        ErrorPacket::ERROR_FAILED_TO_READ_SYMLINK_CODE,
                        array(
                            ErrorPacket::ERROR_FAILED_TO_READ_SYMLINK_PARAM_SYMLINK => $fullItemPath,
                        )
                    );
                }
            } elseif (@is_dir($fullItemPath)) {
                // Exclude WPT management plugin - it should not be transferred
                // TODO generic way to exclude files/dirs
                if ($directoryItem === 'wp-toolkit-management-plugin') {
                    continue;
                }

                // Dump subdirectory recursively:
                // 1) Write directory to the stream.
                $this->fileObjectsWriter->writeDirectory($relativePath, $fullItemPath);
                // 2) Write directory items to the stream recursively.
                $newStartFrom = array();
                if (is_array($startPathParts) && count($startPathParts) > 1 && $directoryItem === $startPathItem) {
                    $newStartFrom = array_slice($startPathParts, 1);
                }
                $this->streamDirectoryContents(
                    $rootItem, $newStartFrom, $startPosition, $excludes, $relativePath, $isListDirectoryItems
                );
                $mode = UnixPermissionsHelper::getModeAsOctalString($fullItemPath);
                if ($mode === false) {
                    $this->fileObjectsWriter->writeError(
                        ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_CODE,
                        array(
                            ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_PARAM_DIR => $fullItemPath,
                        )
                    );
                }
                $this->fileObjectsWriter->writeDirectoryEnd($relativePath, $mode === false ? '' : $mode);
            } elseif (@is_file($fullItemPath)) {
                // Write file to the stream.
                if ($startPosition !== null && $directoryItem === $startPathItem) {
                    $position = $startPosition;
                } else {
                    $position = 0;
                }
                $this->fileObjectsWriter->writeFile($rootItem, $relativePath, $position);
            }
        }

        if ($subdirectory === null) {
            $mode = UnixPermissionsHelper::getModeAsOctalString($rootItem);
            if ($mode === false) {
                $this->fileObjectsWriter->writeError(
                    ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_CODE,
                    array(
                        ErrorPacket::ERROR_FAILED_TO_READ_DIR_MODE_PARAM_DIR => $fullDirectoryPath,
                    )
                );
            }
            $this->fileObjectsWriter->writeDirectoryEnd('', $mode === false ? '' : $mode);
        }
    }

    /**
     * Get list of directory items in the specified directory
     *
     * @param string $directory
     * @return string[]
     */
    public function getDirectoryItems($directory)
    {
        $handle = @opendir($directory);

        if ($handle === false) {
            $this->fileObjectsWriter->writeError(
                ErrorPacket::ERROR_FAILED_TO_READ_DIR_CODE,
                array(
                    ErrorPacket::ERROR_FAILED_TO_READ_DIR_PARAM_DIRECTORY => $directory,
                )
            );
            return array();
        }

        $directoryItems = array();
        while (false !== ($item = @readdir($handle))) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $directoryItems[] = $item;
        }

        @closedir($handle);

        return $directoryItems;
    }
}
