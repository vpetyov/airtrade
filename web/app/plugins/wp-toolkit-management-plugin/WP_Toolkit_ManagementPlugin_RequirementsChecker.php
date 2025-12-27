<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

// ATTENTION: keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

// Pre-requirements checker for WP Toolkit agent.

define('WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_UNSUPPORTED_PHP_VERSION', 'unsupported-php-version');
define('WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_NO_JSON_EXTENSION', 'no-json-extension');
define('WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_WINDOWS_IS_NOT_SUPPORTED', 'windows-is-not-supported');

define('WP_TOOLKIT_MANAGEMENT_PLUGIN_MINIMUM_PHP_VERSION', '5.6');

class WP_Toolkit_ManagementPlugin_RequirementsChecker
{
    /**
     * @var array
     */
    private $errors;

    public function __construct()
    {
        $this->errors = array();
    }

    public function check()
    {
        $this->checkOs();
        $this->checkPhpVersion();
        $this->checkPhpExtensions();
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $code
     * @param string $message
     * @param string[]|null $args
     */
    public function addError($code, $message, $args = null)
    {
        $this->errors[] = array(
            'code' => $code,
            'message' => $message,
            'args' => $args,
        );
    }

    private function checkPhpVersion()
    {
        $phpVersion = phpversion();
        $minimumPhpVersion = WP_TOOLKIT_MANAGEMENT_PLUGIN_MINIMUM_PHP_VERSION;
        if (version_compare($phpVersion, $minimumPhpVersion, '<')) {
            $this->addError(
                WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_UNSUPPORTED_PHP_VERSION,
                "PHP version $phpVersion of the site is not supported by WP Toolkit. " .
                "Please install PHP >= $minimumPhpVersion for WP Toolkit to be able to manage the instance.",
                array(
                    'phpVersion' => $phpVersion,
                    'minimumPhpVersion' => $minimumPhpVersion,
                )
            );
        }
    }

    private function checkPhpExtensions()
    {
        $phpExtensions = get_loaded_extensions();
        if (!in_array('json', $phpExtensions)) {
            $this->addError(
                WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_NO_JSON_EXTENSION,
                "PHP of the WordPress site does not have JSON extension " .
                "which is required for WP Toolkit to work properly. " .
                "Please install and enable the extension."
            );
        }
    }

    private function checkOs()
    {
        if ($this->isWindows()) {
            $this->addError(
                WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_WINDOWS_IS_NOT_SUPPORTED,
                "The WordPress instance is running on Windows. " .
                "WP Toolkit does not support management of such WordPress instances."
            );
        }
    }

    /**
     * @return bool
     */
    private function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
