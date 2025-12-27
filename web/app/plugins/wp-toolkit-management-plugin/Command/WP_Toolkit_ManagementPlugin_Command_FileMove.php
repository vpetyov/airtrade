<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileMove extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_SOURCE_RELATIVE_FILE_PATHS = 'sourceRelativeFilePaths';
    const ARG_TARGET_RELATIVE_FILE_PATHS = 'targetRelativeFilePaths';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/move';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $sourceFilePath = implode(DIRECTORY_SEPARATOR, $args[self::ARG_SOURCE_RELATIVE_FILE_PATHS]);
        $targetFilePath = implode(DIRECTORY_SEPARATOR, $args[self::ARG_TARGET_RELATIVE_FILE_PATHS]);
        rename($sourceFilePath, $targetFilePath);
        return null;
    }
}
