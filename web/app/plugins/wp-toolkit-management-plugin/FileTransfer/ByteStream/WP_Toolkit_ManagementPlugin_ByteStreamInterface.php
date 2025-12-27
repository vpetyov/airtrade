<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\ByteStream;

interface ByteStreamInterface
{
    /**
     * @param int $bytes
     * @return string|null
     */
    public function read($bytes);

    public function close();
}
