<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter;

/**
 * Write data of stream packets to variable.
 */
class MemoryPacketRawDataStreamWriter extends PacketRawDataStreamWriter
{
    private $data;

    public function __construct()
    {
        $this->data = '';
    }

    /**
     * @param int $value
     */
    public function writeInt($value)
    {
        $this->data .= $value . "\n";
    }

    /**
     * @param string $value
     */
    public function writeString($value)
    {
        $this->writeInt(strlen($value));
        $this->data .= $value . "\n";
    }

    /**
     * @param string $packetId
     */
    public function writePacketId($packetId)
    {
        $this->writeInt($packetId);
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
