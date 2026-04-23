<?php
/**
 * Plugin Name: WP OAuth Integration (Simplified)
 * Description: Simplified OAuth2 login for DJO IDP.
 * Version: 0.2.0
 * Author: DJO Amersfoort
 * License: GPL2
 */

defined('ABSPATH') or die("No script kiddies please!");

class WP_OAuth_Integration {

    private $options;
    private $client_id;
    private $client_secret;
    private $prefix = 'woi_idp';
    private $option_name = 'woi_idp_basic_options';

    private $auth_url;
    private $token_url;
    private $profile_url;

    public function __construct() {
        $this->options = get_option($this->option_name);
        $this->client_id = isset($this->options['api_key']) ? $this->options['api_key'] : '';
        $this->client_secret = isset($this->options['api_secret']) ? $this->options['api_secret'] : '';
        $this->auth_url = isset($this->options['auth_url']) ? $this->options['auth_url'] : '';
        $this->token_url = isset($this->options['token_url']) ? $this->options['token_url'] : '';
        $this->profile_url = isset($this->options['profile_url']) ? $this->options['profile_url'] : '';

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('login_form', array($this, 'display_login_button'));
        add_action('init', array($this, 'handle_redirector'));
        add_action('init', array($this, 'process_login'));

        add_shortcode($this->prefix . '_login_link', array($this, 'shortcode_login_link'));
        add_shortcode($this->prefix . '_logout_link', array($this, 'shortcode_logout_link'));
    }

    // --- Settings ---

    public function add_admin_menu() {
        add_options_page('DJO Login Options', 'DJO Login', 'manage_options', $this->prefix . '_settings', array($this, 'options_page_display'));
    }

    public function init_settings() {
        register_setting($this->prefix . '_options_page', $this->option_name);
        add_settings_section($this->prefix . '_section', 'DJO Login Settings', null, $this->prefix . '_options_page');

        $fields = array(
            'api_key' => 'Client ID',
            'api_secret' => 'Client Secret',
            'auth_url' => 'Authorize URL',
            'token_url' => 'Token URL',
            'profile_url' => 'Profile URL',
            'redirect_url' => 'Login Redirect URL',
            'registration_redirect_url' => 'Sign-Up Redirect URL',
        );

        foreach ($fields as $id => $label) {
            add_settings_field($id, $label, array($this, 'render_field'), $this->prefix . '_options_page', $this->prefix . '_section', array('id' => $id));
        }
    }

    public function render_field($args) {
        $id = $args['id'];
        $value = isset($this->options[$id]) ? esc_attr($this->options[$id]) : '';
        echo "<input type='text' name='{$this->option_name}[{$id}]' value='{$value}' class='regular-text'>";
    }

    public function options_page_display() {
        ?>
        <div class="wrap">
            <h1>DJO Login Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields($this->prefix . '_options_page');
                do_settings_sections($this->prefix . '_options_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // --- OAuth Logic ---

    public function get_auth_url($redirect = '') {
        if (empty($this->auth_url)) {
            return '#error_auth_url_not_set';
        }
        $url = add_query_arg('action', $this->prefix . '_redirect', wp_login_url());
        if ($redirect) {
            $url = add_query_arg('redirect_to', $redirect, $url);
        }
        return $url;
    }

    public function handle_redirector() {
        if (!isset($_GET['action']) || $_GET['action'] !== $this->prefix . '_redirect') {
            return;
        }

        if (empty($this->auth_url)) {
            wp_die('Authorize URL not configured.');
        }

        $state = wp_generate_password(16, false);
        $code_verifier = wp_generate_password(64, false);
        $code_challenge = str_replace('=', '', strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'));

        set_transient($this->prefix . '_cv_' . $state, $code_verifier, 300);
        if (isset($_GET['redirect_to'])) {
            set_transient($this->prefix . '_rd_' . $state, esc_url_raw($_GET['redirect_to']), 300);
        }

        setcookie($this->prefix . '_state', $state, time() + 300, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => wp_login_url() . '?action=' . $this->prefix . '_login',
            'response_type' => 'code',
            'scope' => 'user/basic user/names user/email',
            'state' => $state,
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256'
        );
        wp_redirect(add_query_arg($params, $this->auth_url));
        exit;
    }

    public function process_login() {
        if (!isset($_GET['action']) || $_GET['action'] !== $this->prefix . '_login' || !isset($_GET['code']) || !isset($_GET['state'])) {
            return;
        }

        $state = $_GET['state'];
        $saved_state = isset($_COOKIE[$this->prefix . '_state']) ? $_COOKIE[$this->prefix . '_state'] : '';
        setcookie($this->prefix . '_state', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        if (empty($saved_state) || $state !== $saved_state) {
            wp_die('Invalid state. Potential CSRF detected.');
        }

        $code_verifier = get_transient($this->prefix . '_cv_' . $state);
        $redirect = get_transient($this->prefix . '_rd_' . $state);

        delete_transient($this->prefix . '_cv_' . $state);
        delete_transient($this->prefix . '_rd_' . $state);

        if (empty($this->token_url)) {
            wp_die('Token URL not configured.');
        }

        $response = wp_remote_post($this->token_url, array(
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'authorization_code',
                'redirect_uri' => wp_login_url() . '?action=' . $this->prefix . '_login',
                'code' => $_GET['code'],
                'code_verifier' => $code_verifier
            )
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return;
        $data = json_decode(wp_remote_retrieve_body($response));
        if (!isset($data->access_token)) return;

        if (empty($this->profile_url)) {
            return;
        }

        $profile_response = wp_remote_get($this->profile_url, array(
            'headers' => array('Authorization' => 'Bearer ' . $data->access_token)
        ));

        if (is_wp_error($profile_response) || wp_remote_retrieve_response_code($profile_response) !== 200) return;
        $profile = json_decode(wp_remote_retrieve_body($profile_response));
        if (!$profile || !isset($profile->id)) return;

        $this->authenticate_user($profile, $redirect);
    }

    private function authenticate_user($profile, $redirect = '') {
        $users = get_users(array(
            'meta_key' => $this->prefix . '_profile_id',
            'meta_value' => $profile->id,
            'number' => 1
        ));

        if ($users) {
            $user_id = $users[0]->ID;
        } else {
            // Register new user
            $is_begeleider = (strpos($profile->accountType, 'begeleider') !== false) || (strpos($profile->accountType, 'ondersteuning') !== false);
            $username = $profile->firstName . ($is_begeleider ? '_' . $profile->lastName : '') . '_IDP_' . $profile->id;
            $username = sanitize_user($username, true);

            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_pass' => wp_generate_password(16),
                'user_email' => $profile->email,
                'first_name' => $profile->firstName,
                'last_name' => $is_begeleider ? $profile->lastName : '',
                'show_admin_bar_front' => 'false'
            ));
            if (is_wp_error($user_id)) return;
            update_user_meta($user_id, $this->prefix . '_profile_id', $profile->id);
        }

        wp_set_auth_cookie($user_id);
        wp_set_current_user($user_id);

        if (!$redirect) {
            $redirect = !empty($this->options['redirect_url']) ? $this->options['redirect_url'] : admin_url();
        }

        wp_safe_redirect($redirect);
        exit;
    }

    // --- UI & Shortcodes ---

    public function display_login_button() {
        echo '<p><a href="' . $this->get_auth_url() . '" class="button">Login with DJO IDP</a></p>';
    }

    public function shortcode_login_link($atts) {
        if (is_user_logged_in()) return $this->shortcode_logout_link($atts);
        $atts = shortcode_atts(array('text' => 'Login with DJO IDP', 'redirect' => ''), $atts);
        return '<a href="' . $this->get_auth_url($atts['redirect']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function shortcode_logout_link($atts) {
        $atts = shortcode_atts(array('text' => 'Logout'), $atts);
        return '<a href="' . wp_logout_url(home_url('/')) . '">' . esc_html($atts['text']) . '</a>';
    }
}

new WP_OAuth_Integration();
