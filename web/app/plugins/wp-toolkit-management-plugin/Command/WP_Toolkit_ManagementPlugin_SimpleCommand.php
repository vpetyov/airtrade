<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

abstract class WP_Toolkit_ManagementPlugin_SimpleCommand
{
    /**
     * @return string
     */
    public abstract function getName();

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public abstract function execute($args, $payloadReader);
}
