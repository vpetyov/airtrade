<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

use PleskExt\WpToolkit\FileTransfer\ByteStream\StringByteStream;
use PleskExt\WpToolkit\FileTransfer\StreamUnpacker;
use PleskExt\WpToolkit\FileTransfer\PacketHandlers\LocalFileWritePacketHandler;

class WP_Toolkit_ManagementPlugin_Command_FileTransferUploadDirectory extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_DESTINATION_PATH = 'destinationPath';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileTransfer/uploadDirectory';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_ByteStreamInterface.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_StreamUnpacker.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_PacketTypes.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_StringStream.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_BaseStreamPacketReader.php';
        require_once dirname(__FILE__) . '/../FileTransfer/PacketHandlers/WP_Toolkit_ManagementPlugin_PacketHandler.php';
        require_once dirname(__FILE__) . '/../FileTransfer/PacketHandlers/WP_Toolkit_ManagementPlugin_LocalFileWritePacketHandler.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_DirectoryPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_FileChunkPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_FileContinuePacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_FileEndPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_FileStartPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_StreamEndPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/Packets/WP_Toolkit_ManagementPlugin_SymlinkPacket.php';
        require_once dirname(__FILE__) . '/../FileTransfer/WP_Toolkit_ManagementPlugin_PlainStreamPacketReader.php';

        $byteStream = new StringByteStream($payloadReader->readAll());
        $streamUnpacker = new StreamUnpacker(new LocalFileWritePacketHandler($args[self::ARG_DESTINATION_PATH]));
        $streamUnpacker->downloadFromStream($byteStream);
        $byteStream->close();

        exit(0);
    }
}
