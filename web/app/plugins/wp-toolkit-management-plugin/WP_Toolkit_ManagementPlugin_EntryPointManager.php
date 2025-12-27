<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

// ATTENTION: keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

class WP_Toolkit_ManagementPlugin_EntryPointManager
{
    /**
     * @param string $securityKey
     * @param string $securityToken
     */
    public function create($securityKey, $securityToken)
    {
        // Set umask so all files have 0600 permissions ("rw-------") to avoid
        // reading config backups and entry point by other users of the server
        $previousUmask = @umask(0077);
        $this->createCustomEntryPoint($securityKey, $securityToken);
        $this->backupConfig();
        $this->removePluginLinesFromConfig();
        $this->addPluginLinesToConfig($securityKey, $securityToken);
        @umask($previousUmask);
    }

    public function cleanup()
    {
        // Set umask so all files have 0600 permissions ("rw-------") to avoid
        // reading config backups and entry point by other users of the server
        $previousUmask = @umask(0077);
        $this->removeCustomEntryPoint();
        $this->backupConfig();
        $this->removePluginLinesFromConfig();
        @umask($previousUmask);
    }

    /**
     * @param string $securityKey
     * @param string $securityToken
     */
    private function createCustomEntryPoint($securityKey, $securityToken)
    {
        $pluginDirectory = self::getPluginDirectory();
        $fileContent = array_merge(array(
            '<?php'
        ), $this->getLinesWithTemplate(
            $securityKey,
            $securityToken,
            "{$pluginDirectory}wp-toolkit-management-plugin-agent.php"
        ));

        // Create file with 0600 permissions to avoid reading by other users of the server.
        // Do it along with umask to avoid possible security issues in multi-threaded environment
        // (see notes as https://www.php.net/manual/en/function.umask.php)
        @file_put_contents($this->getCustomEntryPointPath(), '');
        @chmod($this->getCustomEntryPointPath(), 0600);

        @file_put_contents($this->getCustomEntryPointPath(), implode("\n", $fileContent), FILE_APPEND);
    }

    private function removeCustomEntryPoint()
    {
        if (is_file($this->getCustomEntryPointPath())) {
            @unlink($this->getCustomEntryPointPath());
        }
    }

    private function backupConfig()
    {
        $backupConfigPath = $this->getWordPressFilePath(
            'wp-toolkit-wp-config-backup-' . uniqid('', true) . '.php'
        );

        // Create file with 0600 permissions to avoid reading by other users of the server.
        // Do it along with umask to avoid possible security issues in multi-threaded environment
        // (see notes as https://www.php.net/manual/en/function.umask.php)
        @file_put_contents($backupConfigPath, '');
        @chmod($backupConfigPath, 0600);

        @file_put_contents($backupConfigPath, file_get_contents($this->getWpConfigPath(), FILE_APPEND));
    }

    /**
     * @param string $securityKey
     * @param $securityToken
     */
    private function addPluginLinesToConfig($securityKey, $securityToken)
    {
        $pluginDirectory = self::getPluginDirectory();
        $wpConfigLines = $this->getLines();
        $linesToInsert = $this->getLinesWithTemplate(
            $securityKey,
            $securityToken,
            "{$pluginDirectory}wp-toolkit-management-plugin-agent.php"
        );

        array_splice($wpConfigLines, 1, 0, $linesToInsert);

        $modifiedWpConfigContents = implode("\n", $wpConfigLines);

        file_put_contents($this->getWpConfigPath(), $modifiedWpConfigContents);
    }

    private function removePluginLinesFromConfig()
    {
        $wpConfigLines = $this->getLines();

        $modifiedLines = array();
        $exclude = false;

        $lines = $this->getLinesWithTemplate('', '', '');
        $firstLine = reset($lines);
        $lastLine = end($lines);
        $linesCount = count($lines);

        $excludeCount = 0;


        foreach ($wpConfigLines as $line) {
            if ($line === $firstLine) {
                $exclude = true;
                $excludeCount++;
            } elseif ($line === $lastLine) {
                $exclude = false;
                $excludeCount++;
            } else {
                if (!$exclude) {
                    $modifiedLines[] = $line;
                } else {
                    $excludeCount++;
                }
            }
        }

        $modifiedWpConfigContents = implode("\n", $modifiedLines);

        if ($excludeCount > 0 && $excludeCount % $linesCount === 0) {
            file_put_contents($this->getWpConfigPath(), $modifiedWpConfigContents);
        }
    }

    /**
     * @return string[]
     */
    private function getLines()
    {
        $contents = file_get_contents($this->getWpConfigPath());
        $lines = $this->getLinesByContents($contents);
        $this->assertFileStructureIsValid($lines);

        return $lines;
    }

    /**
     * @param string $requestCode
     * @param $securityToken
     * @param string $phpFileToRequire
     * @return string[]
     */
    public function getLinesWithTemplate($requestCode, $securityToken, $phpFileToRequire)
    {
        $phpFileToRequire = var_export($phpFileToRequire, true);

        return array(
            "/** [begin] This code used for Remote WP Toolkit */",
            "if (isset(\$_REQUEST['{$requestCode}'])) {",
            "   \$GLOBALS['wpToolkitManagementPluginSecurityToken'] = '$securityToken';",
            "   unset(\$_REQUEST['{$requestCode}']);",
            "   require_once {$phpFileToRequire};",
            "   exit();",
            "}",
            "/** [end] */",
        );
    }

    /**
     * @param string[] $lines
     * @throws WP_Toolkit_ManagementPlugin_AgentException
     */
    private function assertFileStructureIsValid($lines)
    {
        $firstLine = reset($lines);
        $firstLine = trim($firstLine);
        if (strpos($firstLine, '<?') === false) {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                "Cannot perform modifications of wp-config.php file, because it structure is not valid. " .
                "The first line must contain '<?php' or '<?' open tag."
            );
        }
    }

    /**
     * wp-config files could have different end of lines on different OSes.
     * We change it to correct end of line character.
     *
     * @param string $content
     * @return string[]
     */
    private function getLinesByContents($content)
    {
        // Always replace all EOL to linux EOL
        $content = preg_replace("/\r\n|\r|\n/", "\n", $content);
        $lines = explode("\n", $content);

        return $lines;
    }

    /**
     * @return string
     */
    private function getWpConfigPath()
    {
        $wpConfigPath = $this->getWordPressFilePath('wp-config.php');
        if (!file_exists($wpConfigPath)) {
            $wpConfigPath = $this->getWordPressFilePath('..' . DIRECTORY_SEPARATOR . 'wp-config.php');
        }
        if (!file_exists($wpConfigPath)) {
            throw new Exception("Failed to find wp-config.php");
        }
        return $wpConfigPath;
    }

    /**
     * @return string
     */
    private function getCustomEntryPointPath()
    {
        return $this->getWordPressFilePath('wp-toolkit-entry-point.php');
    }

    /**
     * @param string $fileRelativePath
     * @return string
     */
    private function getWordPressFilePath($fileRelativePath)
    {
        return $this->getWordPressDir() . DIRECTORY_SEPARATOR . $fileRelativePath;
    }

    /**
     * @return string
     */
    private function getWordPressDir()
    {
        return realpath(dirname(__FILE__) . '/../../..');
    }

    /**
     * @return string wp-content/plugins/wp-toolkit-management-plugin/
     */
    public static function getPluginDirectory()
    {
        return dirname(preg_replace('/.*(wp-content\/plugins.*)/i', '$1', __FILE__)) . '/';
    }
}
