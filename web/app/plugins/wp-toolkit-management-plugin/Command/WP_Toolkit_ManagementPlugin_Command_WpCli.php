<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

class WP_Toolkit_ManagementPlugin_Command_WpCli extends WP_Toolkit_ManagementPlugin_SimpleCommand
{
    /**
     * @var string wp-content/plugins/wp-toolkit-management-plugin/
     */
    private $pluginDirectory;

    /**
     * @param string $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'wpCli/call';
    }

    /**
     * @param array $args
     * @param WP_Toolkit_ManagementPlugin_StreamReader $payloadReader
     * @return mixed
     */
    public function execute($args, $payloadReader)
    {
        // ATTENTION!!! A lot of magic below, and all that magic is necessary to call WP-CLI command via HTTP request.
        //
        // We need to do that because:
        // - we should use the same PHP interpreter as used by WordPress instance itself;
        // if we use other interpreter (e.g. standard /usr/bin/php) it may have not all required extensions or php.ini
        // settings; also sometimes there is no PHP CLI interpreter at all (but PHP-FPM or other SAPI is present)
        // - functions like "proc_open" may be restricted on the hosting.

        // Make WP-CLI think that it does not work with terminal, but rather with a pipe,
        // otherwise it calls functions which fail
        putenv('SHELL_PIPE=1');

        // Open 3 temporary streams and create STDOUT, STDERR and STDIN constants - usually WP-CLI
        // tries to read/write from these constants, but they are not available in HTTP request
        $stdout = fopen("php://temp", "w");
        $stderr = fopen("php://temp", "w");
        $stdin = fopen("php://temp", "w");

        define('STDOUT', $stdout);
        define('STDERR', $stderr);
        define('STDIN', $stdin);

        // Fill the STDIN stream with request data
        if (isset($args['stdin']) && $args['stdin'] !== '') {
            fwrite($stdin, $args['stdin']);
        }
        fseek($stdin, 0);

        // Fill ENV variables
        if (isset($args['env']) && is_array($args['env'])) {
            foreach ($args['env'] as $varName => $varValue) {
                putenv("{$varName}={$varValue}");
            }
        }

        $argv = $args['args'];

        if ($key = array_search('--stdin-env', $args['args']) !== false) {
            $data = fgets($stdin, getenv('WORDPRESS_STDIN_LENGTH') + 1);
            $argsContent = (array) json_decode($data, true);

            $argv = $assoc_args = [];
            foreach ($argsContent as $name => $value) {
                if ($name === 'WORDPRESS_PROXY_COMMAND_ARGS') {
                    $argv = array_merge($argv, $value);
                } elseif ($name === 'WORDPRESS_PROXY_COMMAND_ASSOC_ARGS') {
                    $assoc_args = $value;
                } else if (is_string($value) && getenv($name) === false) {
                    putenv("$name=$value");
                }
            }

            foreach ($assoc_args as $k => $v) {
                $argv[] = "--{$k}={$v}";
            }
        }


        // Fill global "argv" variable with arguments which should be passed to "wp-cli"
        $GLOBALS['argv'] = array_merge(
            ['wp-cli.phar'], // fake argument - name of executable, not really used
            $argv
        );

        // WP-CLI calls "exit" PHP expression directly, and it calls it even in case of success:
        // the only way to catch it and output structured JSON is to register shutdown function,
        // capture the output and the exit code, and put it to HTTP response
        register_shutdown_function(function () use ($stdout, $stderr) {
            $stdoutBufferContents = ob_get_contents();
            ob_end_clean();

            // Strip prefix
            $prefix = "#!/usr/bin/env php\n";
            $stdoutBufferContents = $this->stripPrefix($stdoutBufferContents, $prefix);

            $stdoutStreamContents = $this->readWholeStreamContentsFromBeginning($stdout);
            $stderrStreamContents = $this->readWholeStreamContentsFromBeginning($stderr);
            // The following line requires patch on WP-CLI side: by default, there is no way
            // to get exit code passed to "exit" PHP expression
            $exitCode = isset($GLOBALS['wpCliExitCode']) ? $GLOBALS['wpCliExitCode'] : 0;

            // Handle situations when parsing of wp-config.php failed. Here we use knowledge
            // about internals of our (Plesk/WPT) patch to WP-CLI, there is no way to catch the
            // error without these internals.
            $stderrEvalWpConfig = '';
            if (isset($GLOBALS['PLESK_WP_CLI_EVAL']) && !$GLOBALS['PLESK_WP_CLI_EVAL']) {
                // PHP Fatal Error occurred while parsing wp-config; e.g. call to undefined function
                $error = error_get_last();
                $stderrEvalWpConfig = $error
                    ? "Failed to parse wp-config.php: {$error['message']} on line {$error['line']}"
                    : "Failed to parse wp-config.php: Unknown error occurred";
                if ($exitCode === 0) {
                    $exitCode = 1;
                }
            }

            $response = [
                'status' => 'success',
                'data' => [
                    'stdout' => $stdoutBufferContents . $stdoutStreamContents,
                    'stderr' => $stderrStreamContents . $stderrEvalWpConfig,
                    'exitCode' => $exitCode
                ],
                'version' => WP_Toolkit_ManagementPlugin_Agent::getPluginVersion()
            ];

            echo json_encode($response);
        });

        // Change directory to the directory with wp-cli.phar and wp-cli.yml, to avoid issues
        // when WP-CLI looks for wp-cli.yml
        chdir($this->pluginDirectory);

        $this->configurePhpErrorReporting();

        ob_start();

        $modernWpCliEntryPoint = dirname(__DIR__) . '/wp-cli/vendor/wp-cli/wp-cli/php/wp-cli.php'; // Modern WP-CLI
        $legacyWpCliEntryPoint = dirname(__DIR__) . '/wp-cli/php/wp-cli.php'; // Old WPT WP-CLI v 1.4

        if (file_exists($modernWpCliEntryPoint)) {
            define('WP_CLI_ROOT', dirname(__DIR__) . '/wp-cli/vendor/wp-cli/wp-cli');
            require_once($modernWpCliEntryPoint);
        } elseif (file_exists($legacyWpCliEntryPoint)) {
            define('WP_CLI_ROOT', dirname(__DIR__) . '/wp-cli');
            require_once($legacyWpCliEntryPoint);
        }

        // should never be called: WP-CLI always initiates exit
        throw new WP_Toolkit_ManagementPlugin_AgentException("WP-CLI was not executed correctly");
    }

    /**
     * @param string $str
     * @param string $prefix
     * @return string
     */
    private function stripPrefix($str, $prefix)
    {
        if (substr($str, 0, strlen($prefix)) === $prefix) {
            return substr($str, strlen($prefix));
        } else {
            return $str;
        }
    }

    /**
     * @param resource $stream
     * @return string
     */
    private function readWholeStreamContentsFromBeginning($stream)
    {
        // Sometimes data cannot be received by 'stream_get_contents' without seeking to start position
        // I have no idea why this happening, it always reproducing when calling 'help' command
        @fseek($stream, 0);
        return stream_get_contents($stream, -1, 0);
    }

    /**
     * Configure PHP error reporting in the same way as in other ways to call WP-CLI.
     */
    private function configurePhpErrorReporting()
    {
        ini_set('display_errors', 'on');
        error_reporting(
            E_ALL & ~E_STRICT & ~E_RECOVERABLE_ERROR
            & ~E_WARNING & ~E_CORE_WARNING & ~E_COMPILE_WARNING & ~E_USER_WARNING
            & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED
        );
    }
}
