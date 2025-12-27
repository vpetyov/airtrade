<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use PleskExt\WpToolkit\FileTransfer\ByteStream\ByteStreamInterface;
use PleskExt\WpToolkit\FileTransfer\Exception\NotEnoughDataException;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryListStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\DirectoryPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileChunkPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileContinuePacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\FileStartPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\StreamEndPacket;
use PleskExt\WpToolkit\FileTransfer\Packets\SymlinkPacket;

class ByteStreamPacketReader
{
    /**
     * @var ByteStreamInterface
     */
    private $stream;

    /**
     * @param ByteStreamInterface $stream
     */
    public function __construct(ByteStreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function nextPacket()
    {
        try {
            $packetType = $this->readLine();
        } catch (NotEnoughDataException $e) {
            return null;
        }

        if ($packetType === '') {
            return null;
        }

        if ($packetType === PacketTypes::DIRECTORY) {
            $length = (int)$this->readLine();
            $name = $this->readBytes($length);
            $mtimeLength = (int)$this->readLine();
            $mtime = $this->readBytes($mtimeLength);
            return new DirectoryPacket($name, $mtime);
        } else if ($packetType === PacketTypes::DIRECTORY_END) {
            $nameLength = (int)$this->readLine();
            $name = $this->readBytes($nameLength);
            $modeLength = (int)$this->readLine();
            $mode = $this->readBytes($modeLength);
            return new DirectoryEndPacket($name, $mode);
        } elseif ($packetType === PacketTypes::DIRECTORY_LIST_START) {
            $length = (int)$this->readLine();
            $name = $this->readBytes($length);
            return new DirectoryListStartPacket($name);
        } elseif ($packetType === PacketTypes::DIRECTORY_LIST_CHUNK) {
            $length = (int)$this->readLine();
            $data = $this->readBytes($length);
            $items = explode("\0", $data);
            return new DirectoryListChunkPacket($items);
        } elseif ($packetType === PacketTypes::DIRECTORY_LIST_END) {
            return new DirectoryListEndPacket();
        } elseif ($packetType === PacketTypes::FILE_START) {
            $length = (int)$this->readLine();
            $name = $this->readBytes($length);
            $mtimeLength = (int)$this->readLine();
            $mtime = $this->readBytes($mtimeLength);
            return new FileStartPacket($name, $mtime);
        } elseif ($packetType === PacketTypes::FILE_CONTINUE) {
            $startPosition = (int)$this->readLine();
            $filePathLength = (int)$this->readLine();
            $filePath = $this->readBytes($filePathLength);
            return new FileContinuePacket($startPosition, $filePath);
        } elseif ($packetType === PacketTypes::FILE_CHUNK) {
            $length = (int)$this->readLine();
            $data = $this->readBytes($length);
            return new FileChunkPacket($data);
        } elseif ($packetType === PacketTypes::FILE_END) {
            $modeLength = (int)$this->readLine();
            $mode = $this->readBytes($modeLength);
            return new FileEndPacket($mode);
        } elseif ($packetType === PacketTypes::SYMLINK) {
            $symlinkPathLength = (int)$this->readLine();
            $symlinkPath = $this->readBytes($symlinkPathLength);
            $targetLength = (int)$this->readLine();
            $target = $this->readBytes($targetLength);
            return new SymlinkPacket($symlinkPath, $target);
        } elseif ($packetType === PacketTypes::ERROR) {
            $errorCodeLength = (int)$this->readLine();
            $errorCode = $this->readBytes($errorCodeLength);
            $errorParamsLength = (int)$this->readLine();
            $errorParamsStr = $this->readBytes($errorParamsLength);
            $errorParams = json_decode($errorParamsStr, true);
            return new ErrorPacket($errorCode, $errorParams);
        } elseif ($packetType === PacketTypes::STREAM_END) {
            return new StreamEndPacket();
        }

        throw new \Exception("Invalid packet type '{$packetType}'");
    }

    /**
     * @return string
     */
    private function readLine()
    {
        $line = '';
        while (true) {
            $char = $this->stream->read(1);
            if ($char === '' && $line !== '') {
                throw new NotEnoughDataException();
            } elseif ($char === '' || $char === "\n") {
                break;
            }
            $line .= $char;
        }
        return $line;
    }

    /**
     * @param int $bytes
     * @return string
     * @throws \Exception
     */
    private function readBytes($bytes)
    {
        $data = '';

        while (true) {
            $c = $this->stream->read($bytes - strlen($data));
            $data .= $c;

            if (strlen($data) === $bytes) {
                break;
            }

            if ($c === '') {
                throw new NotEnoughDataException();
            }
        }

        $c = $this->stream->read(1);

        if ($c === '') {
            throw new NotEnoughDataException();
        }

        if ($c !== "\n") {
            throw new \Exception('Expected newline');
        }

        return $data;
    }
}
