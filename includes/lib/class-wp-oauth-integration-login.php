<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OAuth Login.
 *
 * @class 	WP_OAuth_Integration_Login
 * @version	0.1.0
 */

if (!class_exists('WP_OAuth_Integration_Login')) {

    Class WP_OAuth_Integration_Login {

        // OAuth Application Key
        public $api_key;

        // OAuth Application Secret
        public $api_secret;

        // Stores Access Token
        public $access_token;

        // Stores OAuth Object
        public $oauth;

        // Stores the user redirect after login
        public $user_redirect = false;

        // Stores our plugin options 
        public $plugin_options;
        
        private $provider;

        public function __construct($provider) {
            $this->provider = $provider;

            // This action displays the oauth login button on the default WordPress Login Page
            add_action('login_form', array($this, 'display_login_button'));

            // This action processes any oauth login requests
            add_action('init', array($this, 'process_login'));

            // Get plugin options
            $this->plugin_options = get_option(WP_OAuth_Integration_Factory::get_option_name($this->provider));

            // Set API keys variables
            $this->api_key = isset($this->plugin_options['api_key']) ? $this->plugin_options['api_key'] : '';
            $this->api_secret = isset($this->plugin_options['api_secret']) ? $this->plugin_options['api_secret'] : '';

            // Create new Oauth client
            $this->oauth = new WP_OAuth_Integration_Client($this->api_key, $this->api_secret, '', $this->provider);

            // Set Oauth URLs
            $this->oauth->redirect_uri = wp_login_url() . '?action=' . WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_login';
            $this->oauth->authorize_url = WP_OAuth_Integration_Factory::get_auth_url($this->provider);
            $this->oauth->token_url = WP_OAuth_Integration_Factory::get_token_url($this->provider);
            $this->oauth->api_base_url = WP_OAuth_Integration_Factory::get_base_url($this->provider);

            // Set user token if user is logged in
            if (get_current_user_id()) {
                $this->oauth->access_token = get_user_meta(get_current_user_id(), WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_access_token', true);
            }
            // Add shortcode for generating OAuth Login URL
            add_shortcode(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_login_link', array($this, 'get_login_link'));

            // Start session
            if (!session_id()) {
                session_start();
            }

        }

        // Returns OAuth authorization URL
        public function get_auth_url($redirect = false) {

            $state = wp_generate_password(12, false);
            $authorize_url = $this->oauth->authorizeUrl(array('scope' => 'basic',
                'state' => $state));

            // Store redirect URL in session
            $_SESSION[WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_api_redirect'] = $redirect;

            return $authorize_url;

        }

        // This function displays the login button on the default WP login page
        public function display_login_button() {
            $path = WP_OAuth_Integration_Factory::get_provider_img_button($this->provider) .'/includes/assets/img/oauth-button.png';
     
            // User is not logged in, display login button
            echo "<p><a rel='nofollow' href='" . $this->get_auth_url() . "'>";
            if (file_exists(PLUGINDIR . '/' . $path)){
                echo "<img alt='." .WP_OAuth_Integration_Factory::get_plugin_name($this->provider) . "' src='" . plugins_url() . '/' .$path . "' />";
            }else{
                echo WP_OAuth_Integration_Factory::get_plugin_name($this->provider);
            }
            echo "</a></p>";
        }

        // Logs in a user after he has authorized his OAuth account
        function process_login() {

            // If this is not an oauth sign-in request, do nothing
            if (!$this->is_oauth_signin()) {
                return;
            }

            // If this is a user sign-in request, but the user denied granting access, redirect to login URL
            if (isset($_REQUEST['error']) && $_REQUEST['error'] == 'access_denied') {

                // Get our cancel redirect URL
                $cancel_redirect_url = $this->plugin_options['cancel_redirect_url'];

                // Redirect to login URL if left blank
                if (empty($cancel_redirect_url)) {
                    wp_redirect(wp_login_url());
                }

                // Redirect to our given URL
                wp_safe_redirect($cancel_redirect_url);
            }

            // Another error occurred, create an error log entry
            if (isset($_REQUEST['error'])) {
                $error = $_REQUEST['error'];

                // Log Error and Request Details
                error_log(WP_OAuth_Integration_Factory::get_plugin_name($this->provider) . " Error\nError: $error\nRequest Details: " . print_r($_REQUEST, true));
            }

            // Returns the user's WordPress ID after setting proper redirect URL
            $user_id = $this->wp_auth_user();
            
            if( $user_id == false ){
                return;
            }

            // Signon user by ID
            wp_set_auth_cookie($user_id);

            // Set current WP user so that authentication takes immediate effect without waiting for cookie
            wp_set_current_user($user_id);

            // Store the user's access token as a meta object
            update_user_meta($user_id, WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_access_token', $this->access_token, true);

            // Do action hook that user has authenticated his OAuth account for developers to hook into
            do_action(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_authenticated', $user_id);

            // Validate URL as absolute
            if (filter_var($this->user_redirect, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
                wp_safe_redirect($this->user_redirect);
            }

            // Invalid redirect URL, we'll redirect to admin URL
            else {
                wp_redirect(admin_url());
            }
        }

        /*
         * Get the user's profile and return it as XML
         */
        private function get_user_oauth_profile() {

            // Set Curl Authentication Method
            $this->oauth->curl_authenticate_method = WP_OAuth_Integration_Factory::get_http_method($this->provider);

            // Request access token via OAuth
            $response = $this->oauth->authenticate($_REQUEST['code']);
            $this->access_token = $response->{'access_token'};

            // Retrieve profile from API
            $profile_data = $this->oauth->get(WP_OAuth_Integration_Factory::get_profile_url($this->provider));

            // Parse and return profile
            return WP_OAuth_Integration_Factory::parse_user_profile($profile_data, $this->provider);
        }

        /*
         * Checks if this is an OAuth sign-in request for our plugin
         */
        private function is_oauth_signin() {

            // If no action is requested or the action is not ours
            if (!isset($_REQUEST['action']) || ($_REQUEST['action'] != WP_OAuth_Integration_Factory::get_prefix($this->provider) . "_login")) {
                return false;
            }

            // If a code is not returned, and no error as well, then OAuth did not proceed properly
            if (!isset($_REQUEST['code']) && !isset($_REQUEST['error'])) {
                return false;
            }

            return true;
        }

        /*
         * Authenticate a user in WordPress by his OAuth ID first, and his email address then. IF he doesn't exist, the function creates him based on his retrieved OAuth email address, if OAuth provider supplies email address
         * 
         * @param	string	$xml	The XML response by OAuth which contains profile data
         */
        private function wp_auth_user() {

            // Retrieve the user's OAuth profile
            $oauth_profile = $this->get_user_oauth_profile();

            // Logout any logged in user before we start to avoid any issues arising
            wp_logout();

            // Set default redirect URL to the URL provided by shortcode and stored in session
            $this->user_redirect = $_SESSION[WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_api_redirect'];

            // See if a user with the above OAuth ID exists in our database
            $user_by_oauth_id = get_users(array('meta_key' => WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_profile_id',
                'meta_value' => $oauth_profile['oauth_id']));

            // If he exists, return his ID
            if (count($user_by_oauth_id) == 1) {

                $user_id = $user_by_oauth_id[0]->ID;

                // No custom redirect URL has been specified
                if ($_SESSION[WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_api_redirect'] === false) {

                    // User already exists in our database, redirect him to Login Redirect URL
                    $this->user_redirect = $this->plugin_options['redirect_url'];
                }

                // Update the user's data upon login if the option is enabled
                if ($this->plugin_options['auto_profile_update'] == 'yes') {
                    $this->update_user_data($oauth_profile, $user_id);
                }

                // Do action saying that user logged in via OAuth
                do_action(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_login', $user_id);

                return $user_id;
            }
            // User is signing in for the first time
            else {
                
                $oauth_username = $this->username_format($oauth_profile);
                
                $userdata = array(
                        'user_login' => $oauth_username,
                        'user_pass'  => wp_generate_password(16),
                );
                if( !empty($oauth_profile['email']) ){
                    $userdata['user_email'] = $oauth_profile['email'];
                }

                if( !empty($oauth_profile['first_name']) || !empty($oauth_profile['last_name']) ){
                    $userdata['display_name'] = '';

                    if( !empty($oauth_profile['first_name']) ){
                        $userdata['first_name'] = $oauth_profile['first_name'];
                        $userdata['display_name'] = $oauth_profile['first_name'] .' ';
                    }
                    if( !empty($oauth_profile['last_name']) ){
                        $userdata['last_name'] = $oauth_profile['last_name'];
                        $userdata['display_name'] .= $oauth_profile['last_name'] .' ';
                    }                
                }
                if( !empty($oauth_profile['user_url']) ){
                    $userdata['user_url'] = $oauth_profile['user_url'];
                }
                
                $user_id = wp_insert_user( $userdata );
                
                if( is_wp_error( $user_id ) ) {
                        return $user_id->get_error_message();
                } 

                // Set the user redirect URL
                $this->user_redirect = $this->plugin_options['registration_redirect_url'];

                // Update the user's data, since this is his first sign-in
                $this->update_user_data($oauth_profile, $user_id);

                // The action tells us that the user has registered via OAuth
                do_action(WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_registration', $user_id);

                return $user_id;
            }

            // Does not exist, return false
            return false;
        }

        // Used by shortcode in order to get the login link
        public function get_login_link($attributes = false) {

            // Display the logged in message if user is already logged in
            if (is_user_logged_in()) {

                return $this->plugin_options['logged_in_message'];
            }
            // extract data from array
            $args = array(
                'text' => 'Login With ' . WP_OAuth_Integration_Factory::get_provider_name($this->provider),
                'redirect' => false,
                'class' => ''
            );                        
            
            $path = WP_OAuth_Integration_Factory::get_provider_img_button($this->provider) .'/includes/assets/img/oauth-button.png';
            
            if (file_exists(PLUGINDIR . '/' . $path)){
                $args['img'] = plugins_url() . '/' .$path;
            }
            
            extract(shortcode_atts($args, $attributes));

            $auth_url = $this->get_auth_url($redirect);

            // User has specified an image
            if (isset($attributes['img'])) {
                return "<a href='" . $auth_url . "' class='$class'><img src='" . $img . "' /></a>";
            }

            // User has specified text
            if (isset($attributes['text'])) {
                return "<a href='" . $auth_url . "' class='$class'>" . $text . "</a>";
            }

            // Default fields
            return "<a href='" . $auth_url . "' class='$class'><img src='" . $img . "' /></a>";

        }

        // Updates the user's wordpress data based on his provider data
        private function update_user_data($oauth_data, $user_id) {
            $args = array(
                'ID'            => $user_id,
                'first_name'    => $oauth_data['first_name'],
                'last_name'     => $oauth_data['last_name'],
                'description'   => $oauth_data['description'],
                'user_url'      => $oauth_data['user_url']
            );
            if( !empty($oauth_data['email']) ){
                $args['user_email'] = $oauth_data['email'];
            }

            // Update user data in database
            $result = wp_update_user($args);            

            // Store OAuth ID in database
            update_user_meta($user_id, WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_profile_id', $oauth_data['oauth_id']);

            // Store all profile fields as metadata values
            update_user_meta($user_id, WP_OAuth_Integration_Factory::get_prefix($this->provider) . '_profile', $oauth_data);

            return $result;
        }
        
        /**
         * Get username.
         *
         * @param array $oauth_profile
         * @since 0.1.2
         */
        private function username_format($oauth_profile){
            $username = '';
            
            if( !empty($oauth_profile['first_name']) || !empty($oauth_profile['last_name']) ){
                if( !empty($oauth_profile['first_name']) ){
                    $username = $oauth_profile['first_name'] .'_';
                }
                if( !empty($oauth_profile['last_name']) ){
                    $username .= $oauth_profile['last_name'] .'_';
                }
                
            }elseif( !empty($oauth_profile['oauth_username']) ){
                $username = $oauth_profile['oauth_username'] .'_';
            }
            
            $username .= WP_OAuth_Integration_Factory::get_abbreviation($this->provider);
            
            if( !empty($oauth_profile['oauth_id']) ){
                $username .= '_'.$oauth_profile['oauth_id'];
            }
            
            return $username;
        }

    }

}