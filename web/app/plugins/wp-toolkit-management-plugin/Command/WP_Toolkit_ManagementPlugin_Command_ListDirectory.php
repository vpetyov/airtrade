<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_ListDirectory extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';

    const ARG_LIST_SYSTEM_FILES = 'listSystemFiles';

    /**
     * @return string
     */
    public function getName()
    {
        return 'fileManager/listDirectory';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $absolutePath = implode(
            DIRECTORY_SEPARATOR,
            array_merge([getcwd()], $args[self::ARG_RELATIVE_FILE_PATHS])
        );

        $items = [];

        foreach (scandir($absolutePath) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $items[] = $item;
        }

        $listSystemFiles = $args[self::ARG_LIST_SYSTEM_FILES];
        if (!$listSystemFiles) {
            $items = array_filter($items, function ($item) {
                return substr($item, 0, 1) !== '.';
            });
        }

        return $items;
    }
}
