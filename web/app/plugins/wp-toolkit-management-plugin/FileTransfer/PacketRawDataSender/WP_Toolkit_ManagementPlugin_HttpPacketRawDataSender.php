<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\PacketRawDataSender;

use GuzzleHttp\Client;
use PleskExt\WpToolkit\RemoteServer\Helper\UrlUtils;

class HttpPacketRawDataSender implements PacketRawDataSenderInterface
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string|null
     */
    private $ip;

    /**
     * @param string $url
     * @param string|null $ip
     */
    public function __construct($url, $ip = null)
    {
        $this->url = $url;
        $this->ip = $ip;
    }

    /**
     * @param string $rawData
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPacketsData($rawData)
    {
        $client = new Client();

        $guzzleOptions = [];

        $url = $this->url;

        if (!is_null($this->ip)) {
            $guzzleOptions = [
                'headers' => [
                    'Host' => UrlUtils::getHost($this->url)
                ]
            ];
            $url = UrlUtils::replaceUrlHost($this->url, $this->ip);
        }

        $guzzleOptions['body'] = $rawData;

        $client->request('POST', $url, $guzzleOptions);
    }
}
