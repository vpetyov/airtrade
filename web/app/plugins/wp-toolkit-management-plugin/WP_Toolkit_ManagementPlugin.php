<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

// ATTENTION: keep PHP syntax compatible with old PHP versions, e.g. PHP 5.2, so we could detect
// that situation and provide customer with comprehensive error message.

require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_AgentException.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_EntryPointManager.php');
require_once(dirname(__FILE__) . '/WP_Toolkit_ManagementPlugin_RandomUtils.php');

class WP_Toolkit_ManagementPlugin
{
    const OPTION_SECURITY_KEY = 'wpToolkitManagementPluginSecurityKey';
    const OPTION_SECURITY_TOKEN = 'wpToolkitManagementPluginSecurityToken';
    const OPTION_CONFIG_UPDATE_REQUIRED = 'wpToolkitManagementPluginUpdateConfig';

    /**
     * @var string wp-toolkit-management-plugin/wp-toolkit-management-plugin-agent.php
     */
    private $pluginPath;

    /**
     * @var string wp-toolkit-management-plugin
     */
    private $pluginCode;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var string[]
     */
    private $locale = array(
        'pluginShortName' => 'WPT management plugin',
        'pluginFullName' => 'Plesk WP Toolkit remote management plugin',
        'settingsLink' => 'Settings',
        'settingsPageHeader' => 'WPT Management Plugin Settings',
        'settingsWarning' => 'This WordPress site will no longer be managed by WP Toolkit after changing the settings. You will need to update the plugin end-point URL and security token manually for this WordPress site in WP Toolkit.',
        'secretKeyFormLabel' => 'Secret key for plugin end-point URL',
        'secretKeyFormRequirements' => 'Length of value should be between 16 and 32 characters',
        'securityTokenFormLabel' => 'Security token',
        'securityTokenFormRequirements' => 'Length of value should be between 32 and 64 characters',
        'save' => 'Save settings',
        'pluginUrl' => 'Plugin end-point URL',
        'change' => 'Change settings',
    );

    /**
     * @var string
     */
    private $pluginSettingsPageCode = 'wp-toolkit-agent';

    /**
     * @param array $errors
     */
    public function __construct($errors)
    {
        $this->pluginCode = plugin_dir_path(__FILE__);
        $this->pluginPath = plugin_basename($this->pluginCode . 'wp-toolkit-management-plugin.php');

        $this->errors = is_array($errors) ? $errors : array();

        register_activation_hook($this->pluginPath, 'WP_Toolkit_ManagementPlugin::onActivate');
        register_deactivation_hook($this->pluginPath, array($this, 'onDeactivate'));

        $this->addSettingsPage();
        $this->updateOptions();
    }

    public static function onActivate()
    {
        self::generateSecurityProperties();
        self::createEntryPoint();
    }

    public function onDeactivate()
    {
        self::deleteEntryPoint();
        $this->unRegisterSettingsGroup();
    }

    public static function generateSecurityProperties()
    {
        update_option(self::OPTION_SECURITY_KEY, 'q' . WP_Toolkit_ManagementPlugin_RandomUtils::generateRandomString(31));
        update_option(self::OPTION_SECURITY_TOKEN, WP_Toolkit_ManagementPlugin_RandomUtils::generateRandomString(64));
    }

    /**
     * @return string[]
     */
    public static function getSettings()
    {
        return array(
            'securityKey' => get_option(self::OPTION_SECURITY_KEY),
            'securityToken' => get_option(self::OPTION_SECURITY_TOKEN),
        );
    }

    private function addSettingsPage()
    {
        add_action('admin_menu', array($this, 'addAdminMenuAction'));
        add_filter('plugin_action_links_' . $this->pluginPath, array($this, 'addLinks'));
    }

    /**
     * Add link to settings page from "Installed plugins"
     *
     * @param $links
     * @return array
     */
    public function addLinks($links)
    {
        $link = admin_url("admin.php?page={$this->pluginSettingsPageCode}");
        return array_merge(array(
            $this->pluginCode => "<a href='{$link}'>{$this->locale['settingsLink']}</a>",
        ), $links);
    }

    public function addAdminMenuAction()
    {
        add_options_page(
            $this->locale['pluginFullName'],
            $this->locale['pluginShortName'],
            'administrator',
            $this->pluginSettingsPageCode,
            array($this, 'settingsPage')
        );
        add_action('admin_init', array($this, 'registerSettingsGroup'));
    }

    public function settingsPage()
    {
        wp_enqueue_script('jquery');

        $securityKey = get_option(self::OPTION_SECURITY_KEY, false);
        $securityToken = get_option(self::OPTION_SECURITY_TOKEN, false);
        $isSettingsExists = $securityKey !== false && $securityToken !== false;
        $pluginUrl = site_url() . "/?{$securityKey}";
        ?>
        <style>
            .hidden-block {
                display: none;
            }
            .input-error {
                border-color: #dc2020 !important;
                padding-right: calc(1.5em + 0.75rem);
            }
            .input-error-text {
                display: none;
            }
            .input-error ~ .input-error-text {
                display: block;
            }
            .warning-message {
                padding: 0.75rem 1.25rem;
                border: 1px solid #ffe1ac;
                border-radius: 0.25rem;
                color: #855b00;
                background-color: #ffedb1;
            }
        </style>
        <script>
            jQuery(document).ready(function(){
                var $settingsTable = jQuery('.js-wp-toolkit-management-plugin-settings-table');
                var $settingsForm = jQuery('.js-wp-toolkit-management-plugin-form-table');
                jQuery('.js-change-settings').click(function(e){
                    e.preventDefault();
                    jQuery(this).hide();
                    $settingsTable.hide();
                    $settingsForm.show();
                });
                jQuery('.js-settings-form').submit(function(e){
                    jQuery(this).find('input').removeClass('input-error');
                    var $securityKey = jQuery('.js-security-key');
                    var $securityToken = jQuery('.js-security-token');
                    var $isValid = true;
                    if ($securityKey.val().trim().length < 16 || $securityKey.val().trim().length > 32) {
                        $securityKey.addClass('input-error');
                        $isValid = false;
                    }
                    if ($securityToken.val().trim().length < 32 || $securityToken.val().trim().length > 64) {
                        $securityToken.addClass('input-error');
                        $isValid = false;
                    }

                    if (!$isValid) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        </script>
        <div class="wrap container">
            <div class="row">
                <h2><?php echo $this->locale['settingsPageHeader'] ?></h2>
            </div>
<?php foreach ($this->errors as $error) { ?>
            <div class="error-message"><?php echo htmlspecialchars($error['message']); ?></div>
<?php } ?>
            <form method='post' action='options.php' class='js-settings-form'>
                <?php settings_fields("{$this->pluginCode}SettingsGroup"); ?>
                <table class="form-table <?php echo ($isSettingsExists? 'hidden-block' : '')?> js-wp-toolkit-management-plugin-form-table">
                    <tbody>
                    <tr class="<?php echo (!$isSettingsExists? 'hidden-block' : '')?>">
                        <td colspan="2"><div class="warning-message"><?php echo $this->locale['settingsWarning']; ?></div></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo $this->locale['secretKeyFormLabel']; ?></th>
                        <td>
                            <input type="text"
                                   class="regular-text js-security-key"
                                   id="secretKey"
                                   name="<?php echo self::OPTION_SECURITY_KEY ?>"
                                   value="<?php echo $securityKey; ?>"
                            />
                            <div class="input-error-text">
                                <?php echo $this->locale['secretKeyFormRequirements']; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo $this->locale['securityTokenFormLabel']; ?></th>
                        <td>
                            <input type="text"
                                   class="regular-text js-security-token"
                                   id="securityToken"
                                   name="<?php echo self::OPTION_SECURITY_TOKEN ?>"
                                   value="<?php echo $securityToken; ?>"
                            />
                            <div class="input-error-text">
                                <?php echo $this->locale['securityTokenFormRequirements']; ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><button type="submit" class='button button-primary'><?php echo $this->locale['save']; ?></button></td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table <?php echo (!$isSettingsExists? 'hidden-block' : '')?> js-wp-toolkit-management-plugin-settings-table">
                    <tbody>
                    <tr>
                        <th scope="row"><?php echo $this->locale['pluginUrl']; ?></th>
                        <td><?php echo htmlspecialchars($pluginUrl); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo $this->locale['securityTokenFormLabel']; ?></th>
                        <td><?php echo htmlspecialchars($securityToken); ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button class='button js-change-settings'><?php echo $this->locale['change']; ?></button></td>
                    </tr>
                    </tbody>
                </table>

                <input type="hidden" name="pluginUrl" value="<?php echo htmlspecialchars($pluginUrl); ?>" />
                <input type="hidden" name="securityToken" value="<?php echo htmlspecialchars($securityToken); ?>" />
            </form>
        </div>
        <?php
    }

    public function registerSettingsGroup()
    {
        register_setting("{$this->pluginCode}SettingsGroup", self::OPTION_SECURITY_KEY);
        register_setting("{$this->pluginCode}SettingsGroup", self::OPTION_SECURITY_TOKEN);
        add_action('update_option_' . self::OPTION_SECURITY_KEY, array($this, 'forceUpdateConfig'));
        add_action('update_option_' . self::OPTION_SECURITY_TOKEN, array($this, 'forceUpdateConfig'));
    }

    private function unRegisterSettingsGroup()
    {
        unregister_setting("{$this->pluginCode}SettingsGroup", self::OPTION_SECURITY_KEY);
        unregister_setting("{$this->pluginCode}SettingsGroup", self::OPTION_SECURITY_TOKEN);
        unregister_setting("{$this->pluginCode}SettingsGroup", self::OPTION_CONFIG_UPDATE_REQUIRED);
        delete_option(self::OPTION_SECURITY_KEY);
        delete_option(self::OPTION_SECURITY_TOKEN);
        delete_option(self::OPTION_CONFIG_UPDATE_REQUIRED);
    }

    public function forceUpdateConfig()
    {
        register_setting("{$this->pluginCode}SettingsGroup", self::OPTION_CONFIG_UPDATE_REQUIRED);
        update_option(self::OPTION_CONFIG_UPDATE_REQUIRED, true);
    }

    public function updateOptions()
    {
        if (get_option(self::OPTION_CONFIG_UPDATE_REQUIRED)) {
            self::deleteEntryPoint();
            self::createEntryPoint();
            //On old WP this function could be not defined on this stage
            if (function_exists('unregister_setting')) {
                unregister_setting("{$this->pluginCode}SettingsGroup", self::OPTION_CONFIG_UPDATE_REQUIRED);
            }
            delete_option(self::OPTION_CONFIG_UPDATE_REQUIRED);
        }
    }

    private static function createEntryPoint()
    {
        $entryPointManager = new WP_Toolkit_ManagementPlugin_EntryPointManager();
        $entryPointManager->create(
            get_option(self::OPTION_SECURITY_KEY),
            get_option(self::OPTION_SECURITY_TOKEN)
        );
    }

    private static function deleteEntryPoint()
    {
        $entryPointManager = new WP_Toolkit_ManagementPlugin_EntryPointManager();
        $entryPointManager->cleanup();
    }
}
