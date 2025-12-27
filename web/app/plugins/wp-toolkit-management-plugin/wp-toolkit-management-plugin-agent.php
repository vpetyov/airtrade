<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

define('WP_TOOLKIT_MANAGEMENT_PLUGIN_OPTION_SKIP_REQUIREMENTS_CHECK', 'skip-requirements-check');
define('WP_TOOLKIT_MANAGEMENT_PLUGIN_OPTION_TIME_LIMIT', 'time-limit');

// Run requirements checker: check PHP version, OS, etc.
// ATTENTION: before the requirements checker has finished it work,
// keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

if (!isset($_REQUEST[WP_TOOLKIT_MANAGEMENT_PLUGIN_OPTION_SKIP_REQUIREMENTS_CHECK])) {
    require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_RequirementsChecker.php');
    $requirementsChecker = new WP_Toolkit_ManagementPlugin_RequirementsChecker();
    $requirementsChecker->check();
    if ($requirementsChecker->hasErrors()) {
        // Do not use JSON extension as it could be missing
        $errorsStrs = array();
        foreach ($requirementsChecker->getErrors() as $error) {
            $errorCode = $error['code'];
            $errorMessage = $error['message'];
            $argsStr = '';
            if (isset($error['args']) && is_array($error['args'])) {
                $argItemStrs = array();
                foreach ($error['args'] as $argName => $argValue) {
                    $argItemStrs[] = "\"$argName\": \"$argValue\"";
                }
                $allArgsStr = implode(", ", $argItemStrs);
                $argsStr = ",\n      \"args\": { $allArgsStr }";
            }
            $errorsStrs[] = "    {\n      \"code\": \"$errorCode\",\n      \"message\": \"$errorMessage\"$argsStr\n    }";
        }
        $errorsStr = implode(",\n", $errorsStrs);

        echo "{\n  \"status\": \"error\",\n  \"errors\": [\n$errorsStr\n  ]\n}\n";
        die();
    }
}

// Configure PHP options
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1); // always display errors to keep debugging simple
set_time_limit(
    isset($_REQUEST[WP_TOOLKIT_MANAGEMENT_PLUGIN_OPTION_TIME_LIMIT])
        ? $_REQUEST[WP_TOOLKIT_MANAGEMENT_PLUGIN_OPTION_TIME_LIMIT]
        : 60
);

// Handle the request
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_Agent.php');
$agent = new WP_Toolkit_ManagementPlugin_Agent();
$agent->handleRequest();
