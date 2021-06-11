<?php
/* 
 * Copyright (C) 2017 Samer Bechara <sam@thoughtengineer.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace WPIDP;

class Plugin_Config {

    // Define the plugin slug
    public static $slug = 'idp-login';

    // Define the provider name
    public static $provider_name = 'DJO IDP';

    // Define plugin prefix that will be used in DB to help prevent conflicts with similar plugins
    public static $prefix = 'woi_idp';

    // Define plugin name
    public static $plugin_name = 'DJO Login';

        // Define plugin abbreviation
    public static $abbreviation = 'IDP';

    // Define plugin URL
    public static $plugin_url;

    // Define authorize URL
    public static $authorize_url = 'https://leden.djoamersfoort.nl/o/authorize/';

    // Define token URL
    public static $token_url = 'https://leden.djoamersfoort.nl/o/token/';

    // Define API Base URL
    public static $base_url = 'https://leden.djoamersfoort.nl/api/v1/member/details';

    // Define the URL to create OAuth application for this provider
    public static $apps_url = 'https://leden.djoamersfoort.nl/o/applications';

    // Define our preferred HTTP authentication method
    public static $http_auth_method = 'POST';

    // Define the access token name per the API
    public static $access_token_name = 'access_token';

    // The URL at which to retrieve the user profile
    public static $profile_url = 'https://leden.djoamersfoort.nl/api/v1/member/details';

    // Parses and returns back profile data
    public static function parse_profile_data($profile_data) {
            $decoded_data = json_decode($profile_data);

            $is_begeleider = (strpos($decoded_data->accountType, 'begeleider') !== false);
            $is_begeleider |= (strpos($decoded_data->accountType, 'ondersteuning') !== false);
            $lastname = $decoded_data->lastName;

            // Return result
            return array(
                'oauth_id'          => $decoded_data->id,
                'oauth_username'    => '',
                'first_name'        => $decoded_data->firstName,
                'last_name'         => $is_begeleider ? $lastname : '',
                'description'       => '',
                'user_url'          => '',
                'profile_picture'   => '',
                'email'             => $decoded_data->email,
            );
    }

    // Splits the full name into first and last name
    public static function split_name($full_name) {
            if ($full_name == false) {
                return array( 'first_name' => '', 'last_name' => '' );
            }
            $first_name = $last_name = '';

            $arr = explode(' ', $full_name);
            $num = count($arr);

            if ($num == 2) {
                list($first_name, $last_name) = $arr;
            } else {
                list($first_name, $middle_name, $last_name) = $arr;
            }

            return array( 'first_name' => $first_name, 'last_name' => $last_name );
    }
}
