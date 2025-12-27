<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataSender;

use PleskExt\WpToolkit\RemoteAgentInstance\RemoteAgentTransport;

class RemoteAgentPacketRawDataSender implements PacketRawDataSenderInterface
{
    /**
     * @var RemoteAgentTransport
     */
    private $transport;

    /**
     * @var string
     */
    private $destinationPath;

    /**
     * @param RemoteAgentTransport $transport
     * @param string $destinationPath
     */
    public function __construct(RemoteAgentTransport $transport, $destinationPath)
    {
        $this->transport = $transport;
        $this->destinationPath = $destinationPath;
    }

    /**
     * @param string $rawData
     * @throws \Exception
     */
    public function sendPacketsData($rawData)
    {
        $this->transport->uploadDirectory($rawData, $this->destinationPath);
    }
}
