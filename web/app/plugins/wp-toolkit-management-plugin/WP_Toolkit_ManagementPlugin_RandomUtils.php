<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

// ATTENTION: keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

class WP_Toolkit_ManagementPlugin_RandomUtils
{
    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length)
    {
        srand((float)microtime()*1000000);
        $symbols = array_merge(
            (array)range('a', 'z'),
            (array)range('A', 'Z'),
            (array)range(0, 9)
        );
        $randomSymbols = array();

        for ($i = 0; $i < $length; $i++) {
            $randomSymbols[] = $symbols[rand(0, count($symbols) - 1)];
        }
        shuffle($randomSymbols);
        return implode("", $randomSymbols);
    }
}
