<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileRemove extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/remove';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $filePath = implode(DIRECTORY_SEPARATOR, $args[self::ARG_RELATIVE_FILE_PATHS]);
        WP_Toolkit_ManagementPlugin_FileUtils::removePath($filePath);
        return null;
    }
}
