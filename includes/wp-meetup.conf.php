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

namespace WPMeetup;

class Plugin_Config {
 
    // Define the plugin slug
    public static $slug = 'meetup-login';
    
    // Define the provider name
    public static $provider_name = 'Meetup';
    
    // Define plugin prefix that will be used in DB to help prevent conflicts with similar plugins
    public static $prefix = 'woi_meetup';
    
    // Define plugin name
    public static $plugin_name = 'Meetup Login';
    
        // Define plugin abbreviation
    public static $abbreviation = 'ME';
    
    // Define plugin URL
    public static $plugin_url;
    
    // Define authorize URL
    public static $authorize_url = 'https://secure.meetup.com/oauth2/authorize';
    
    // Define token URL
    public static $token_url = 'https://secure.meetup.com/oauth2/access';
    
    // Define API Base URL
    public static $base_url = 'https://api.meetup.com/2';
    
    // Define the URL to create OAuth application for this provider
    public static $apps_url = 'https://secure.meetup.com/meetup_api/oauth_consumers/';
    
    // Define our preferred HTTP authentication method
    public static $http_auth_method = 'POST';
    
    // Define the access token name per the API
    public static $access_token_name = 'access_token';
    
    // The URL at which to retrieve the user profile
    public static $profile_url = 'https://api.meetup.com/2/member/self';
    
    // Parses and returns back profile data
    public static function parse_profile_data($profile_data) {     
           // TODO: Add API Call to retrieve user profile and replace dummy data
            $decoded_data = json_decode($profile_data);

            // Get first and last name from full name
            $split_name = self::split_name($decoded_data->name);
            
            // Return result
            return array(
                'oauth_id'          => isset($decoded_data->id) ? $decoded_data->id : '',
                'oauth_username'    => sanitize_title($decoded_data->name),
                'first_name'        => $split_name['first_name'],
                'last_name'         => $split_name['last_name'],
                'description'       => isset($decoded_data->bio) ? $decoded_data->bio : '',
                'user_url'          => isset($decoded_data->link) ? $decoded_data->link : '',
                'profile_picture'   => isset($decoded_data->photo->thumb_link) ? $decoded_data->photo->thumb_link : '',
                'email'             => ''
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