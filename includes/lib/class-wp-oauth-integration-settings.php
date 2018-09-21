<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings.
 *
 * @class 	WP_OAuth_Integration_Settings
 * @version	0.1.0
 */
class WP_OAuth_Integration_Settings {

    // This stores our plugin options
    private $options;
    
    private $provider;

    /*
     * Class constructor, initializes menu and settings page
     */
    public function __construct($provider) {
        $this->provider = $provider;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        
        $this->options = get_option(WP_OAuth_Integration_Factory::get_option_name($this->provider));
    }

    /*
     * Adds an admin menu
     */
    public function add_admin_menu() {
        add_options_page('WP ' . WP_OAuth_Integration_Factory::get_plugin_name($this->provider) . ' Options Page', WP_OAuth_Integration_Factory::get_plugin_name($this->provider), 'manage_options', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_settings', array($this, 'options_page_display'));
    }

    /*
     * Displays the options page
     */
    public function options_page_display() {
        ?>
        <form action='options.php' method='post'>

        <?php
        settings_fields(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page');
        do_settings_sections(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page');
        submit_button();
        ?>

        </form>
            <?php
        }

        /*
         * Initializes our settings
         */
        public function init_settings() {

            register_setting(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_option_name($this->provider));

            add_settings_section(
                    WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', __(WP_OAuth_Integration_Factory::get_plugin_name($this->provider) . ' Plugin Settings', $this->provider), array($this, 'basic_options_section_callback'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page'
            );

            add_settings_field(
                    'api_key', __('Your ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' API Key', $this->provider), array($this, 'text_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'api_key',
                'field_description' => 'Retrieved from <a href="' . WP_OAuth_Integration_Factory::get_apps_url($this->provider) . '" target="_blank">' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' Developer Portal</a>. Follow the previous link, create an application and paste the key here',
                'field_help' => 'help text goes here')
            );

            add_settings_field(
                    'api_secret', __('Your ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' API Secret', $this->provider), array($this, 'text_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'api_secret',
                'field_description' => 'This is another key that can be found when you create the application following the previous link as well. Paste it here.')
            );

            add_settings_field(
                    'redirect_url', __('Login Redirect URL', $this->provider), array($this, 'text_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'redirect_url',
                'field_description' => 'The absolute URL to redirect users to after login. If left blank or points to external host, will redirect to the dashboard page.')
            );

            add_settings_field(
                    'registration_redirect_url', __('Sign-Up Redirect URL', $this->provider), array($this, 'text_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'registration_redirect_url',
                'field_description' => 'Users are redirected to this URL when they register via their ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' account. This is useful if you want to show them a one-time welcome message after registration. If left blank or points to external host, will redirect to the dashboard page.')
            );

            add_settings_field(
                    'cancel_redirect_url', __('Cancel Redirect URL', $this->provider), array($this, 'text_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'cancel_redirect_url',
                'field_description' => 'Users are redirected to this URL when they click Cancel on the ' . $this->provider . ' Authentication page. This is useful if you want to show them a different option if for some reason they do not want to login with their ' . $this->provider . ' account. If left blank or points to external host, will redirect back to default WordPress login page.')
            );

            add_settings_field(
                    'auto_profile_update', sprintf( __('Retrieve %s  profile data everytime?', $this->provider), strtolower(WP_OAuth_Integration_Factory::get_provider_name($this->provider)) ), array($this, 'select_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'auto_profile_update',
                'field_description' => 'This option allows you to pull in the users data the first time, upon registration but not overwrite all of their information every time they login with the ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' button. This is useful if users spend time creating a custom profile and then they later use the login with ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider) . ' button. Disable this if you do not want their information to be overwritten')
            );

            add_settings_field(
                    'override_profile_photo', __("Override the user's profile picture?", $this->provider), array($this, 'select_field_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'override_profile_photo',
                'field_description' => 'When enabled, this option fetches the user\'s profile picture from ' . $this->provider . ' and overrides the default gravatar.com user profile picture used by WordPress. If the plugin is setup to retrive new profile data on every login, the profile picture will be retrieved as well.')
            );

            add_settings_field(
                    'logged_in_message', __('Logged In Message', $this->provider), array($this, 'text_area_display'), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_options_page', WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_general_options_section', array('field_name' => 'logged_in_message',
                'field_description' => 'Enter a message you would like to show for logged in users in place of the login button. If left blank, the button is hidden and no message is shown.')
            );
        }

        /*
         * Displays a text field setting, called back by the add_settings_field function
         * @param   array   $field_options  Passed by the add_settings_field callback function
         */
        public function text_field_display($field_options) {

            // Get the text field name
            $field_name = $field_options['field_name'];
            ?>
        <input type='text' name='<?php echo WP_OAuth_Integration_Factory::get_option_name($this->provider); ?>[<?php echo $field_name; ?>]' value='<?php echo $this->get_field_value($field_name) ?>'>
        <p class="description"><?php echo isset($field_options['field_description']) ? $field_options['field_description'] : ''; ?></p>
        <?php
    }

    /*
     * Displays a text area setting, called back by the add_settings_field function
     * @param   array   $field_options  Passed by the add_settings_field callback function
     */
    public function text_area_display($field_options) {

        $field_name = $field_options['field_name'];
        ?>
        <textarea cols='40' rows='5' name='<?php echo WP_OAuth_Integration_Factory::get_option_name($this->provider); ?>[<?php echo $field_name; ?>]'><?php echo $this->get_field_value($field_name) ?></textarea>
        <p class="description"><?php echo isset($field_options['field_description']) ? $field_options['field_description'] : ''; ?></p>
        <?php
    }

    /*
     * Returns the field's value
     */
    private function get_field_value($field_name) {

        return isset($this->options[$field_name]) ? $this->options[$field_name] : '';
    }

    /*
     * Displays a select field
     */
    function select_field_display($field_options) {

        $field_name = $field_options['field_name'];
        $field_value = $this->get_field_value($field_name);
        ?>
        <select name='<?php echo WP_OAuth_Integration_Factory::get_option_name($this->provider); ?>[<?php echo $field_name; ?>]'>
            <option value='yes' <?php selected($field_value, 'yes'); ?>>Yes</option>
            <option value='no' <?php selected($field_value, 'no'); ?>>No</option>
        </select>
        <p class="description"><?php echo isset($field_options['field_description']) ? $field_options['field_description'] : ''; ?></p>
        <?php
    }

    /*
     * Rendered at the start of the options section
     */
    function basic_options_section_callback() {

        echo __('For installation instructions, please visit <a href="http://thoughtengineer.com/wordpress-' . $this->provider . '-plugin/" target="_blank">Installation Instructions Page</a>', $this->provider);
    }

}