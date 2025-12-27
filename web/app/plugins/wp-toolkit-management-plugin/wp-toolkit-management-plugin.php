<?php
/*
 * Plugin Name: WebPros WP Toolkit remote management plugin
 * Plugin URI:  https://ext.plesk.com/packages/00d002a7-3252-4996-8a08-aa1c89cf29f7-wp-toolkit
 * Author:      WebPros International GmbH
 * Author URI:  https://www.plesk.com
 * Description: The plugin allows you to manage this WordPress site remotely from Plesk or cPanel control panel using WP Toolkit
 * Version:     1.1.1
 */
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

// ATTENTION: keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_RequirementsChecker.php');
$checker = new WP_Toolkit_ManagementPlugin_RequirementsChecker();
$checker->check();

require(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin.php');
return (new WP_Toolkit_ManagementPlugin($checker->getErrors()));
