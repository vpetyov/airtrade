<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

class DirectoryTransferOptions
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var null|string[]
     */
    private $startPathParts;

    /**
     * @var null|int
     */
    private $startPosition;

    /**
     * @var string[]
     */
    private $excludes = [];

    /**
     * @var bool
     */
    private $listDirectoryItems = false;

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return null|string[]
     */
    public function getStartPathParts()
    {
        return $this->startPathParts;
    }

    /**
     * @param null|string[] $startPathParts
     * @return $this
     */
    public function setStartPathParts($startPathParts)
    {
        $this->startPathParts = $startPathParts;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getStartPosition()
    {
        return $this->startPosition;
    }

    /**
     * @param null|int $startPosition
     * @return $this
     */
    public function setStartPosition($startPosition)
    {
        $this->startPosition = $startPosition;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludes()
    {
        return $this->excludes;
    }

    /**
     * @param string[] $excludes
     * @return $this
     */
    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isListDirectoryItems()
    {
        return $this->listDirectoryItems;
    }

    /**
     * @param bool $listDirectoryItems
     * @return $this
     */
    public function setListDirectoryItems($listDirectoryItems)
    {
        $this->listDirectoryItems = $listDirectoryItems;
        return $this;
    }
}
