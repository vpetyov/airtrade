<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileCreateDirectoryAbsolute extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_ABSOLUTE_PATH = 'absolutePath';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/createDirectoryAbsolute';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $filePath = $args[self::ARG_ABSOLUTE_PATH];
        if (!is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }
        return null;
    }
}
