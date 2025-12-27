<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_FileUtils.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_SecureLinuxPermissions.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_SimpleCommand.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileGetAbsolutePath.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileGetContents.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FilePutContents.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileIsExists.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileIsExistAbsolute.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileUploadFile.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileTouch.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileRemoveAbsolute.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileRemove.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileCreateDirectory.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileCreateDirectoryAbsolute.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileMove.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_ListDirectory.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_WpCli.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsCheck.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsFix.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileTransferDownloadDirectory.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_FileTransferUploadDirectory.php');
require_once(dirname(__FILE__) . '/Command/WP_Toolkit_ManagementPlugin_Command_DatabaseCreateDump.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_AgentException.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_RequestData.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_Agent.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_EntryPointManager.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_StreamReader.php');

class WP_Toolkit_ManagementPlugin_Agent
{
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_NO_REQUEST_DATA = 'no-request-data';
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_NOT_DEFINED = 'security-token-not-defined';
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_NOT_SPECIFIED = 'security-token-not-specified';
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_IS_NOT_CORRECT = 'security-token-not-correct';
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_COMMAND_NOT_SPECIFIED = 'command-not-specified';
    const WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_PLUGIN_UPDATE_REQUIRED = 'plugin-update-required';

    /**
     * @var WP_Toolkit_ManagementPlugin_SimpleCommand[]
     */
    private $commands;

    public function __construct()
    {
        $this->commands = array(
            new WP_Toolkit_ManagementPlugin_Command_FileGetContents(),
            new WP_Toolkit_ManagementPlugin_Command_FilePutContents(),
            new WP_Toolkit_ManagementPlugin_Command_FileGetAbsolutePath(),
            new WP_Toolkit_ManagementPlugin_Command_FileIsExists(),
            new WP_Toolkit_ManagementPlugin_Command_FileIsExistAbsolute(),
            new WP_Toolkit_ManagementPlugin_Command_FileUploadFile(),
            new WP_Toolkit_ManagementPlugin_Command_FileTouch(),
            new WP_Toolkit_ManagementPlugin_Command_FileRemoveAbsolute(),
            new WP_Toolkit_ManagementPlugin_Command_FileRemove(),
            new WP_Toolkit_ManagementPlugin_Command_FileCreateDirectory(),
            new WP_Toolkit_ManagementPlugin_Command_FileCreateDirectoryAbsolute(),
            new WP_Toolkit_ManagementPlugin_Command_FileMove(),
            new WP_Toolkit_ManagementPlugin_Command_ListDirectory(),
            new WP_Toolkit_ManagementPlugin_Command_WpCli(
                WP_Toolkit_ManagementPlugin_EntryPointManager::getPluginDirectory()
            ),
            new WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsCheck(),
            new WP_Toolkit_ManagementPlugin_Command_SecurityCheckerPermissionsFix(),
            new WP_Toolkit_ManagementPlugin_Command_FileTransferUploadDirectory(),
            new WP_Toolkit_ManagementPlugin_Command_FileTransferDownloadDirectory(),
            new WP_Toolkit_ManagementPlugin_Command_DatabaseCreateDump(),
        );
    }

    public function handleRequest()
    {
        try {
            $requestData = $this->readRequestData();
            $command = $this->getCommandByName($requestData->getCommand());
            $result = $command->execute($requestData->getArgs(), $requestData->getPayloadReader());
            $this->sendSuccessResponse($result);
        } catch (WP_Toolkit_ManagementPlugin_AgentException $e) {
            $this->sendErrorResponse($e->getAgentErrorCode(), $e->getMessage());
        } catch (Exception $e) {
            $this->sendErrorResponse(null, $e->getMessage());
        }
    }

    /**
     * @return WP_Toolkit_ManagementPlugin_RequestData
     * @throws WP_Toolkit_ManagementPlugin_AgentException
     */
    private function readRequestData()
    {
        $fp = fopen('php://input', 'r');
        $streamReader = new WP_Toolkit_ManagementPlugin_StreamReader($fp);
        $requestStr = $streamReader->readUntilChar("\0");
        $requestData = json_decode($requestStr, true);

        if (!is_array($requestData)) {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Request data is not provided',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_NO_REQUEST_DATA
            );
        }

        if (
            !isset($GLOBALS['wpToolkitManagementPluginSecurityToken']) ||
            empty($GLOBALS['wpToolkitManagementPluginSecurityToken'])
        ) {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Security token is not defined',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_NOT_DEFINED
            );
        }

        if (!isset($requestData['securityToken'])) {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Security token is not specified',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_NOT_SPECIFIED
            );
        }

        if ($requestData['securityToken'] !== $GLOBALS['wpToolkitManagementPluginSecurityToken']) {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Security token is incorrect',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_SECURITY_TOKEN_IS_NOT_CORRECT
            );
        }

        if ($requestData['version'] !== self::getPluginVersion() && $requestData['version'] !== 'any') {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Plugin version is not equal, plugin update is required.',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_PLUGIN_UPDATE_REQUIRED
            );
        }

        unset($GLOBALS['wpToolkitManagementPluginSecurityToken']);

        $command = isset($requestData['command']) ? $requestData['command'] : null;

        if ($command === null || $command === '') {
            throw new WP_Toolkit_ManagementPlugin_AgentException(
                'Command is not specified',
                self::WP_TOOLKIT_MANAGEMENT_PLUGIN_ERROR_CODE_COMMAND_NOT_SPECIFIED
            );
        }

        $args = isset($requestData['args']) ? $requestData['args'] : array();

        return new WP_Toolkit_ManagementPlugin_RequestData($requestData['command'], $args, $streamReader);
    }

    /**
     * @param mixed $data
     */
    private function sendSuccessResponse($data)
    {
        echo json_encode(array(
            'status' => 'success',
            'data' => $data,
            'version' => self::getPluginVersion(),
        ));
    }

    /**
     * @param string $agentErrorCode
     * @param string $message
     */
    private function sendErrorResponse($agentErrorCode, $message)
    {
        echo json_encode(array(
            'status' => 'error',
            'code' => $agentErrorCode,
            'message' => $message,
            'version' => self::getPluginVersion()
        ));
    }

    /**
     * @param string $commandName
     * @return WP_Toolkit_ManagementPlugin_SimpleCommand
     * @throws WP_Toolkit_ManagementPlugin_AgentException
     */
    private function getCommandByName($commandName)
    {
        $commands = $this->getCommandsByNames();

        if (!isset($commands[$commandName])) {
            throw new WP_Toolkit_ManagementPlugin_AgentException("Specified command not found: {$commandName}");
        }

        return $commands[$commandName];
    }

    /**
     * @return WP_Toolkit_ManagementPlugin_SimpleCommand[]
     */
    private function getCommandsByNames()
    {
        $commandsByNames = array(
        );
        foreach ($this->commands as $command) {
            $commandsByNames[$command->getName()] = $command;
        }
        return $commandsByNames;
    }

    /**
     * @return string
     */
    public static function getPluginVersion()
    {
        $pluginFile = dirname(__FILE__) . '/wp-toolkit-management-plugin.php';
        $allComments = array_filter(
            token_get_all( file_get_contents( $pluginFile ) ), function($entry)
            {
                return $entry[0] == T_COMMENT;
            }
        );
        $fileComment = array_shift( $allComments )[1];
        preg_match('/Version:.*/', $fileComment, $matches);
        $version = trim(explode(':', $matches[0])[1]);
        return $version;
    }
}
