<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_RequestData
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var array|null
     */
    private $args;

    /**
     * @var WP_Toolkit_ManagementPlugin_StreamReader
     */
    private $payloadReader;

    /**
     * @param string $command
     * @param array|null $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     */
    public function __construct($command, $args, $payloadReader)
    {
        $this->command = $command;
        $this->args = $args;
        $this->payloadReader = $payloadReader;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return array|null
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return WP_Toolkit_ManagementPlugin_StreamReader
     */
    public function getPayloadReader()
    {
        return $this->payloadReader;
    }
}
