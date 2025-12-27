<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Helper;

class JsonHelper
{
    public static function jsonEncodeUnicodeSafe($data): string
    {
        $options = JSON_THROW_ON_ERROR;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $options |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        return json_encode($data, $options);
    }
}
