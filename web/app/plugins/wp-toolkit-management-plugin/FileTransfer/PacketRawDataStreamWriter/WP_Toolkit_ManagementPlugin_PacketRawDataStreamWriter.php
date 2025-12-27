<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter;

abstract class PacketRawDataStreamWriter
{
    /**
     * @param int $value
     */
    abstract function writeInt($value);

    /**
     * @param string $value
     */
    abstract function writeString($value);

    /**
     * @param string $packetId
     */
    abstract function writePacketId($packetId);
}
