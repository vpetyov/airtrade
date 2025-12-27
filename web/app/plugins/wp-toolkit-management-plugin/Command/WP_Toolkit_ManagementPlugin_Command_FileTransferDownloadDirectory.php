<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

use PleskExt\WpToolkit\FileTransfer\DirectoryStreamWriterHttpResponse;
use PleskExt\WpToolkit\FileTransfer\DirectoryTransferOptions;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter\EchoPacketRawDataStreamWriter;

class WP_Toolkit_ManagementPlugin_Command_FileTransferDownloadDirectory extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_ABSOLUTE_PATH = 'absolutePath';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileTransfer/downloadDirectory';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_DirectoryStreamWriterHttpResponse.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_DirectoryTransferOptions.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_StreamPacketWriter.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_PlainStreamPacketWriter.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_FileSystemStreamPacketWriterInterface.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_DirectoryStreamPacketWriter.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_FileObjectsWriter.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_DirectoryContentsWriter.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_PacketTypes.php';

        $options = new DirectoryTransferOptions();
        $options->setDirectory($args[self::ARG_ABSOLUTE_PATH]);
        $writer = new DirectoryStreamWriterHttpResponse();
        $writer->write($options, new EchoPacketRawDataStreamWriter());

        exit(0);
    }
}
