<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_SecureLinuxPermissions
{
    const WP_CONFIG_SECURE_PERMISSIONS = '600';
    const FILES_SECURE_PERMISSIONS = '644';
    const DIRS_SECURE_PERMISSIONS = '755';

    /**
     * @var int
     */
    static $currentUid;

    /**
     * @return string[]
     */
    public static function getSecureFilePermissions()
    {
        $userPermissions = array('0', '2', '4', '6');
        $groupPermissions = array('0', '4');
        $otherPermissions = array('0', '4');

        return self::getPermissionCombinations($userPermissions, $groupPermissions, $otherPermissions);
    }

    /**
     * @return string[]
     */
    public static function getSecureDirPermissions()
    {
        $userPermissions = array('0', '1', '2', '3', '4', '5', '6', '7');
        $groupPermissions = array('0', '1', '4', '5');
        $otherPermissions = array('0', '1', '4', '5');

        return self::getPermissionCombinations($userPermissions, $groupPermissions, $otherPermissions);
    }

    /**
     * Get list of directories which must be secured in recursive manner (like "-R" option of "chmod" utility).
     *
     * @return string[]
     */
    public static function getDirectoriesToSecureRecursive()
    {
        return array(
            'wp-admin',
            'wp-content',
            'wp-includes',
        );
    }

    /**
     * @param string $directory
     * @return string[]
     */
    public static function listDirectoryFiles($directory)
    {
        $files = array();

        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (is_file($directory . DIRECTORY_SEPARATOR . $item)) {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * @param string $directory
     * @return string[]
     */
    public static function listDirectoryDirectories($directory)
    {
        $directories = array();

        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (is_dir($directory . DIRECTORY_SEPARATOR . $item)) {
                $directories[] = $item;
            }
        }

        return $directories;
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function getMode($filename)
    {
        $stat = stat($filename);
        return (string)(decoct($stat['mode']) % 1000);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isOwnedByCurrentUser($path)
    {
        return fileowner($path) == self::getCurrentUid();
    }

    public static function getWpConfigPath()
    {
        if (file_exists('wp-config.php')) {
            return 'wp-config.php';
        } else if (file_exists('..' . DIRECTORY_SEPARATOR . 'wp-config.php')) {
            return '..' . DIRECTORY_SEPARATOR . 'wp-config.php';
        } else {
            throw new WP_Toolkit_ManagementPlugin_AgentException("Failed to find wp-config.php");
        }
    }

    /**
     * @return int
     */
    public static function getCurrentUid()
    {
        if (is_null(self::$currentUid)) {
            // Get current UID by creating temporary file and checking its permissions.
            // We do not use POSIX extension functions, or exec('id'), as the extension could be
            // missing, and the functions could be disabled
            $filename = tempnam(".", "wordpress-toolkit-test");
            file_put_contents($filename, "");
            self::$currentUid = fileowner($filename);
            unlink($filename);
        }
        return self::$currentUid;
    }

    /**
     * @param string[] $userPermissions
     * @param string[] $groupPermissions
     * @param string[] $otherPermissions
     * @return string[]
     */
    private static function getPermissionCombinations($userPermissions, $groupPermissions, $otherPermissions)
    {
        return array_map(
            function ($combination) {
                return implode('', $combination);
            },
            self::cartesianProduct(
                $userPermissions,
                $groupPermissions,
                $otherPermissions
            )
        );
    }

    /**
     * Cartesian product is a mathematical operation that returns a set from multiple sets.
     *
     * For example cartesianProduct(['a', 'b'], ['c', 'd'])
     * will result in [['a', 'c'], ['a', 'd'], ['b', 'c'], ['b', 'd']]
     *
     * @param array $arrays
     * @return array
     */
    private static function cartesianProduct(array ...$arrays)
    {
        if (count($arrays) === 0) {
            return array();
        } elseif (count($arrays) === 1) {
            $result = array();
            $firstArray = $arrays[0];
            foreach ($firstArray as $firstArrayItem) {
                $result[] = array($firstArrayItem);
            }
            return $result;
        } else {
            $result = array();
            $firstArray = $arrays[0];
            $combinations = self::cartesianProduct(...array_slice($arrays, 1));
            foreach ($firstArray as $firstArrayItem) {
                foreach ($combinations as $combination) {
                    $result[] = array_merge(
                        array($firstArrayItem), $combination
                    );
                }
            }
            return $result;
        }
    }
}
