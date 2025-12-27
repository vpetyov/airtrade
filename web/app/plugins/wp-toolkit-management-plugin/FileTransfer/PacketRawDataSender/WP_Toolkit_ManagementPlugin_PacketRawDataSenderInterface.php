<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataSender;

interface PacketRawDataSenderInterface
{
    /**
     * @param string $rawData
     * @throws \Exception
     */
    public function sendPacketsData($rawData);
}
