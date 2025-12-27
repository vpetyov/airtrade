<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileGetAbsolutePath extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/getAbsolutePath';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        return implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                [realpath(dirname(__FILE__) . '/../../../..')],
                $args[self::ARG_RELATIVE_FILE_PATHS]
            )
        );
    }
}
