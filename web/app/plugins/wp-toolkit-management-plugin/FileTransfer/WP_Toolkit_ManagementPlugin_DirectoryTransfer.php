<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer;

use GuzzleHttp\Client;
use PleskExt\WpToolkit\FileTransfer\ByteStream\GuzzleResponseByteStream;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataSender\HttpPacketRawDataSender;
use PleskExt\WpToolkit\FileTransfer\Packets\StreamEndPacket;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\DirectoryChunkedStreamPacketWriter;
use PleskExt\WpToolkit\RemoteServer\Helper\UrlUtils;

class DirectoryTransfer
{
    /**
     * @param string $sourceUrl
     * @param string|null $sourceIp
     * @param string $destinationUrl
     * @param string|null $destinationIp
     */
    public function transfer(
        $sourceUrl,
        $sourceIp,
        $destinationUrl,
        $destinationIp
    ) {
        $client = new Client();

        $guzzleOptions = [];

        if (!is_null($sourceIp)) {
            $guzzleOptions = [
                'headers' => [
                    'Host' => UrlUtils::getHost($sourceUrl)
                ]
            ];
            $sourceUrl = UrlUtils::replaceUrlHost($sourceUrl, $sourceIp);
        }

        $response = $client->request('GET', $sourceUrl, $guzzleOptions);

        $byteStream = new GuzzleResponseByteStream($response->getBody());

        $streamPacketReader = new ByteStreamPacketReader($byteStream);

        $dataSender = new HttpPacketRawDataSender($destinationUrl, $destinationIp);
        $directoryStreamPacketWriter = new DirectoryChunkedStreamPacketWriter($dataSender);

        while (true) {
            $packet = $streamPacketReader->nextPacket();

            if ($packet === null || $packet instanceof StreamEndPacket) {
                $directoryStreamPacketWriter->writePacketStreamEnd();
                break;
            }

            $directoryStreamPacketWriter->writePacket($packet);
        }
    }
}
