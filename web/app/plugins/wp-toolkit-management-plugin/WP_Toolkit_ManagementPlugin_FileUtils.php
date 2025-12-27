<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_FileUtils
{
    public static function removePath($path)
    {
        if (is_dir($path)) {
            $items = @scandir($path);
            if ($items) {
                foreach (scandir($path) as $item) {
                    if ( $item == '.' || $item == '..' ) {
                        continue;
                    }
                    self::removePath($path . DIRECTORY_SEPARATOR . $item);
                }
            }

            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}
