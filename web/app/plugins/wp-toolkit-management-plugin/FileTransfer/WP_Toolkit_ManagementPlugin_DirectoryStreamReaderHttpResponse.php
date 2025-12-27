<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use GuzzleHttp\Client;
use PleskExt\WpToolkit\FileTransfer\ByteStream\GuzzleResponseByteStream;
use PleskExt\WpToolkit\FileTransfer\PacketHandlers\LocalFileWritePacketHandler;
use PleskExt\WpToolkit\RemoteServer\Helper\UrlUtils;

class DirectoryStreamReaderHttpResponse
{
    public function download($destinationPath, $url, $ip = null)
    {
        $client = new Client();

        $guzzleOptions = [];

        if (!is_null($ip)) {
            $guzzleOptions = [
                'headers' => [
                    'Host' => UrlUtils::getHost($url)
                ]
            ];
            $url = UrlUtils::replaceUrlHost($url, $ip);
        }

        $response = $client->request('GET', $url, $guzzleOptions);

        $byteStream = new GuzzleResponseByteStream($response->getBody());
        $streamUnpacker = new StreamUnpacker(new LocalFileWritePacketHandler($destinationPath));
        $streamUnpacker->downloadFromStream($byteStream);
        $byteStream->close();
    }
}
