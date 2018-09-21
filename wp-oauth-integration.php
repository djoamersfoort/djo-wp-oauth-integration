<?php
/**
 * Plugin Name: WP OAuth Integration
 * Plugin URI: 
 * Description: Allow users to login/register via their different provider accounts.
 * Version: 0.1.3
 * Author: The Thought Engineer
 * Author URI: http://thoughtengineer.com/
 * Text Domain: wp-oauth-integration
 * Domain Path: /languages 
 * License: GPL2
 * 
 * This program is GLP but; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of.
 *
 * @author  The Thought Engineer <sam@thoughtengineer.com>
 * @package WordPress OAuth Integration
 */

// Do not allow direct file access
defined('ABSPATH') or die("No script kiddies please!");

if (! defined( 'WP_OAUTH_INTEGRATION_FILE' ) ) {
	define( 'WP_OAUTH_INTEGRATION_FILE', __FILE__ );
}
if (! defined( 'WOI_PLUGIN_NAME' ) ) {
	define( 'WOI_PLUGIN_NAME', 'wordpress-oauth-integration' );
}

// Include the main WP_OAuth_Integration_Main class.
if ( ! class_exists( 'WP_OAuth_Integration_Main' ) ) {
        require_once dirname(WP_OAUTH_INTEGRATION_FILE) . '/includes/class-wp-oauth-integration-main.php';
}

register_uninstall_hook(WP_OAUTH_INTEGRATION_FILE, array('WP_OAuth_Integration_Main', 'uninstall'));

/**
 * Main instance of WP_OAuth_Integration_Main.
 *
 * Returns the main instance of WP_OAuth_Integration_Main to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return WP_OAuth_Integration_Main
 */
if ( ! function_exists( 'WOIM' ) ) {
    function WOIM() {
            return WP_OAuth_Integration_Main::instance();
    }
}

WOIM();