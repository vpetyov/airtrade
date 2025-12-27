<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Helper;

class UnixPermissionsHelper
{
    /**
     * @param string $path
     * @return bool|string
     */
    public static function getModeAsOctalString($path)
    {
        $stat = stat($path);
        if ($stat === false) {
            return false;
        }
        return substr(decoct($stat['mode']), -3);
    }

    /**
     * @param string $path
     * @return bool|int
     */
    public static function getModeAsNumber($path)
    {
        $stat = stat($path);
        if ($stat === false) {
            return false;
        }
        return octdec(substr(decoct($stat['mode']), -3));
    }

    /**
     * @param string $mode
     * @return int
     */
    public static function stringToOctalNumberMode($mode)
    {
        return octdec($mode);
    }
}
