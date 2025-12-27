<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_AgentException extends Exception
{
    /**
     * @var string|null
     */
    private $agentErrorCode;

    public function __construct($message = "", $agentErrorCode = null)
    {
        parent::__construct($message);
        $this->agentErrorCode = $agentErrorCode;
    }

    public function getAgentErrorCode()
    {
        return $this->agentErrorCode;
    }
}
