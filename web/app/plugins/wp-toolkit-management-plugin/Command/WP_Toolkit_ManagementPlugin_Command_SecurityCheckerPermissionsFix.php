<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsFix extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'securityChecker/permissions/fix';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        $wpConfigPath = WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getWpConfigPath();

        if (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($wpConfigPath)) {
            chmod($wpConfigPath, octdec(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::WP_CONFIG_SECURE_PERMISSIONS));
        }

        $secureFilePermissions = WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureFilePermissions();

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryFiles('.') as $file) {
            if (
                WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($file) &&
                !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($file), $secureFilePermissions)
            ) {
                chmod($file, octdec(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::FILES_SECURE_PERMISSIONS));
            }
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getDirectoriesToSecureRecursive() as $directory) {
            $this->secureDirectory($directory);
        }

        return null;
    }

    /**
     * @param string $directoryPath
     * @return bool
     */
    private function secureDirectory($directoryPath)
    {
        if (
            WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($directoryPath) &&
            !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($directoryPath), WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureDirPermissions())
        ) {
            chmod($directoryPath, octdec(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::DIRS_SECURE_PERMISSIONS));
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryFiles($directoryPath) as $item) {
            $filePath = $directoryPath . DIRECTORY_SEPARATOR . $item;
            if (
                WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::isOwnedByCurrentUser($filePath) &&
                !in_array(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getMode($filePath), WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::getSecureFilePermissions())
            ) {
                chmod($filePath, octdec(WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::FILES_SECURE_PERMISSIONS));
            }
        }

        foreach (WP_Toolkit_ManagementPlugin_SecureLinuxPermissions::listDirectoryDirectories($directoryPath) as $item) {
            $subdirectoryPath = $directoryPath . DIRECTORY_SEPARATOR . $item;
            $this->secureDirectory($subdirectoryPath);
        }

        return true;
    }
}
