<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_DatabaseCreateDump extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    const ARG_RELATIVE_FILE_PATHS = 'relativeFilePaths';
    const ARG_HOST = 'host';
    const ARG_USER = 'user';
    const ARG_PASSWORD = 'password';
    const ARG_DATABASE = 'database';

    /**
     * @return string
     */
    public function getName()
    {
        return 'database/createDump';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     */
    public function execute($args, $payloadReader)
    {
        $filePathEscaped = escapeshellarg(implode(DIRECTORY_SEPARATOR, $args[self::ARG_RELATIVE_FILE_PATHS]));
        $hostEscaped = escapeshellarg($args[self::ARG_HOST]);
        $userEscaped = escapeshellarg($args[self::ARG_USER]);
        // TODO pass password in secure way
        $passwordEscaped = escapeshellarg($args[self::ARG_PASSWORD]);
        $databaseEscaped = escapeshellarg($args[self::ARG_DATABASE]);
        $mysqlCmd = is_executable('/usr/bin/mariadb-dump') ? '/usr/bin/mariadb-dump' : 'mysqldump';
        $command = (
            $mysqlCmd . " " .
            "--no-defaults " .
            "-h$hostEscaped -u$userEscaped -p$passwordEscaped $databaseEscaped > $filePathEscaped"
        );
        shell_exec($command);
    }
}
