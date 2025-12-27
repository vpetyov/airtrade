<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

class PacketTypes
{
    const DIRECTORY = 'directoryStartPacket';
    const DIRECTORY_END = 'directoryEndPacket';
    const DIRECTORY_LIST_START = 'directoryListStartPacket';
    const DIRECTORY_LIST_CHUNK = 'directoryListChunkPacket';
    const DIRECTORY_LIST_END = 'directoryListEndPacket';
    const FILE_START = 'fileStartPacket';
    const FILE_CONTINUE = 'fileContinuePacket';
    const FILE_CHUNK = 'fileChunkPacket';
    const FILE_END = 'fileEndPacket';
    const SYMLINK = 'symlinkPacket';
    const ERROR = 'errorPacket';
    const STREAM_END = 'streamEndPacket';
}
