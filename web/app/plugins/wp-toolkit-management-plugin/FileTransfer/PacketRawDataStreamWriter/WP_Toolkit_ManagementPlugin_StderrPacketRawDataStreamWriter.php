<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter;

/**
 * Write data of stream packets to STDERR in plain form, without any encryption and compression.
 *
 * No buffering is performed - the packets are written immediately.
 */
class StderrPacketRawDataStreamWriter extends PacketRawDataStreamWriter
{
    private $stream;

    public function __construct()
    {
        $this->stream = fopen('php://stderr', 'w');
    }

    /**
     * @param int $value
     */
    public function writeInt($value)
    {
        fwrite($this->stream, $value . "\n");
    }

    /**
     * @param string $value
     */
    public function writeString($value)
    {
        $this->writeInt(strlen($value));
        fwrite($this->stream, $value . "\n");
    }

    /**
     * @param string $packetId
     */
    public function writePacketId($packetId)
    {
        $this->writeInt($packetId);
    }
}
