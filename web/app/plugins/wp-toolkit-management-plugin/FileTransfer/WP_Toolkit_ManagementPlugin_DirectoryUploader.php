<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\PacketRawDataSender\HttpPacketRawDataSender;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\DirectoryChunkedStreamPacketWriter;

class DirectoryUploader
{
    /**
     * @param string $sourcePath
     * @param string $url
     * @param string|null $ip
     */
    public function upload($sourcePath, $url, $ip)
    {
        $dataSender = new HttpPacketRawDataSender($url, $ip);
        $directoryStreamPacketWriter = new DirectoryChunkedStreamPacketWriter($dataSender);
        $fileObjectsWriter = new FileObjectsWriter($directoryStreamPacketWriter);
        $directoryContentsWriter = new DirectoryContentsWriter($fileObjectsWriter);

        $directoryTransferOptions = new DirectoryTransferOptions();
        $directoryTransferOptions->setDirectory($sourcePath);

        $directoryContentsWriter->streamDirectoryContents(
            $directoryTransferOptions->getDirectory(),
            $directoryTransferOptions->getStartPathParts(),
            $directoryTransferOptions->getStartPosition(),
            $directoryTransferOptions->getExcludes()
        );
        $directoryStreamPacketWriter->writePacketStreamEnd();
    }
}
