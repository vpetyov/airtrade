<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter;

/**
 * Write data of stream packets to HTTP response in plain form, without any encryption and compression.
 *
 * No buffering is performed - the packets are written immediately.
 */
class EchoPacketRawDataStreamWriter extends PacketRawDataStreamWriter
{
    /**
     * @param int $value
     */
    public function writeInt($value)
    {
        echo $value . "\n";
    }

    /**
     * @param string $value
     */
    public function writeString($value)
    {
        $this->writeInt(strlen($value));
        echo $value . "\n";
    }

    /**
     * @param string $packetId
     */
    public function writePacketId($packetId)
    {
        $this->writeInt($packetId);
    }
}
