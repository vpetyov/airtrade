<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileTouch extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/touch';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $filePath = implode(DIRECTORY_SEPARATOR, $args[self::ARG_RELATIVE_FILE_PATHS]);
        touch($filePath);
        return null;
    }
}
