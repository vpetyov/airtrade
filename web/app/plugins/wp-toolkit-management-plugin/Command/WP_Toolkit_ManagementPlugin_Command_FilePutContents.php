<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_FilePutContents extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';

    const ARG_CONTENTS = 'contents';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/filePutContents';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $filePath = implode(DIRECTORY_SEPARATOR, $args[self::ARG_RELATIVE_FILE_PATHS]);
        return file_put_contents($filePath, base64_decode($args[self::ARG_CONTENTS]));
    }
}
