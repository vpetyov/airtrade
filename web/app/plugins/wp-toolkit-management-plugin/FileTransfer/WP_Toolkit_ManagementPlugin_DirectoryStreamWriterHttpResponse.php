<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter\PacketRawDataStreamWriter;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\DirectoryStreamPacketWriter;

class DirectoryStreamWriterHttpResponse
{
    /**
     * @param DirectoryTransferOptions $directoryTransferOptions
     * @param PacketRawDataStreamWriter $streamPacketWriter
     */
    public function write($directoryTransferOptions, $streamPacketWriter)
    {
        $directoryStreamPacketWriter = new DirectoryStreamPacketWriter($streamPacketWriter);
        $fileObjectsWriter = new FileObjectsWriter($directoryStreamPacketWriter);
        $directoryContentsWriter = new DirectoryContentsWriter($fileObjectsWriter);
        $directoryContentsWriter->streamDirectoryContents(
            $directoryTransferOptions->getDirectory(),
            $directoryTransferOptions->getStartPathParts(),
            $directoryTransferOptions->getStartPosition(),
            $directoryTransferOptions->getExcludes(),
            null,
            $directoryTransferOptions->isListDirectoryItems()
        );
        $directoryStreamPacketWriter->writePacketStreamEnd();
    }
}
