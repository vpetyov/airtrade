<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsCheck extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'securityChecker/permissions/check';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return bool
     */
    public function execute($args, $payloadReader)
    {
        $wpConfigPath = WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getWpConfigPath();
        if (
            WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($wpConfigPath) &&
            WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($wpConfigPath) !== WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::WP_CONFIG_SECURE_PERMISSIONS
        ) {
            return false;
        }

        $secureFilePermissions = WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureFilePermissions();

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryFiles('.') as $file) {
            if (
                WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($file) &&
                !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($file), $secureFilePermissions)
            ) {
                return false;
            }
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getDirectoriesToSecureRecursive() as $directory) {
            if (!$this->isValidPermissionsDir($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function isValidPermissionsDir($directoryPath)
    {
        if (
            WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($directoryPath) &&
            !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($directoryPath), WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureDirPermissions())
        ) {
             return false;
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryFiles($directoryPath) as $item) {
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . $item;
            if (
                WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($filePath) &&
                !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($filePath), WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureFilePermissions())
            ) {
                return false;
            }
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryDirectories($directoryPath) as $item) {
            $subdirectoryPath = $directoryPath . DIRECTORY_SEPARATOR . $item;
            if (!$this->isValidPermissionsDir($subdirectoryPath)) {
                return false;
            }
        }

        return true;
    }
}
