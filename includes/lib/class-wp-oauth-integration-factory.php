<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Factory class.
 *
 * @class 	WP_OAuth_Integration_Factory
 * @version	0.1.0
 */
class WP_OAuth_Integration_Factory {

    // This is set by the main plugin file
    public static $plugin_slug = false;
    
    protected static $providers = array();
    
    public static function add_provider($provider) {
            self::$providers[] = $provider;

            return self::$providers;
    }
    
    public static function get_providers(){
        return self::$providers;
    }
    
     public static function get_provider_class($provider) {
        return 'WP'.ucwords( $provider ).'\Plugin_Config';
    }
     
    public static function get_slug() {
        return self::$plugin_slug;
    }
    
    public static function get_provider_img_button($provider) {
        //For default provider
        if( $provider == 'meetup' ){
            return WOI_PLUGIN_NAME;
        }
        return WOI_PLUGIN_NAME.'-'.$provider;
    }
    
    public static function get_option_name($provider) {
        $provider_class = self::get_provider_class($provider); 
        
        return $provider_class::$prefix.'_basic_options';
    }
    
    public static function get_http_method($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$http_auth_method;
    }
    
    public static function get_access_token_name($provider) {     
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$access_token_name;
    }
    
    public static function get_user_meta_name($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$prefix.'_profile';
    }    
    
    public static function get_plugin_name($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$plugin_name;
    }
    
    public static function get_prefix($provider) {    
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$prefix;
    }
    
    public static function get_auth_url($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$authorize_url;
    }
    
    public static function get_token_url($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$token_url;
    }    
    
    public static function get_base_url($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$base_url;
    }
    
    public static function get_plugin_url($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return plugins_url().'/'.$provider_class::$slug.'/';
    }
    
    public static function get_provider_name($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$provider_name;
    }
    
    public static function get_apps_url($provider) {     
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$apps_url;
    }
    
    public static function get_profile_url($provider) {        
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$profile_url;
    }
    
    public static function parse_user_profile($profile_data, $provider) {
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::parse_profile_data($profile_data);
    }
    
    public static function get_abbreviation($provider) {
        $provider_class = self::get_provider_class($provider);
        
        return $provider_class::$abbreviation;
    }

}