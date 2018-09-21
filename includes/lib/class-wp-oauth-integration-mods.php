<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OAuth Mods.
 *
 * @class 	WP_OAuth_Integration_Mods
 * @version	0.1.0
 */

if (!class_exists('WP_OAuth_Integration_Mods')) {

    Class WP_OAuth_Integration_Mods {
        
        private $provider;

        public function __construct($provider) {
            $this->provider = $provider;

            // Add filter to override avatar
            add_filter('get_avatar', array($this, 'override_user_photo'), 1, 5);
        }

        /*
         * This function overrides the user photo with the OAuth provider's supplied profile photo
         */
        public function override_user_photo($avatar, $id_or_email, $size, $default, $alt) {
            // Get plugin option
            $plugin_options = get_option(WP_OAuth_Integration_Factory::get_option_name($this->provider));

            // Do nothing if the option is not enabled
            if ($plugin_options['override_profile_photo'] !== 'yes') {
                return $avatar;
            }

            // Assume that no user is logged in
            $user = false;

            // If the ID passed is numeric, get user by ID
            if (is_numeric($id_or_email)) {

                $id = (int) $id_or_email;
                $user = get_user_by('id', $id);

                // Object passed, Get user by ID part of that object
            } elseif (is_object($id_or_email)) {

                if (!empty($id_or_email->user_id)) {
                    $id = (int) $id_or_email->user_id;
                    $user = get_user_by('id', $id);
                }
                // Get user by email
            } else {
                $user = get_user_by('email', $id_or_email);
            }

            // User has been successfully returned
            if ($user && is_object($user)) {

                $user_profile = get_user_meta($user->ID, WP_OAuth_Integration_Factory::get_user_meta_name($this->provider), true);

                // No data for this user exists, return (E.g. user is an admin)
                if (empty($user_profile)) {
                    return $avatar;
                }

                // Get the user's profile pic
                if( !empty($user_profile['profile_picture']) ){
                    return "<img alt='{$alt}' src='{$user_profile['profile_picture']}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
                }
            }

            return $avatar;

        }

    }

}