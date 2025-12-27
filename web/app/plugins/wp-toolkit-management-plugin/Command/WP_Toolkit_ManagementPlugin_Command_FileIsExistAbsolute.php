<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FileIsExistAbsolute extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_ABSOLUTE_PATH = 'absolutePath';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/isExistAbsolute';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        return file_exists($args[self::ARG_ABSOLUTE_PATH]);
    }
}
