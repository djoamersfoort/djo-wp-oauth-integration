<?php
/**
 * WordPress OAuth Integration setup
 *
 * @author   The Thought Engineer
 * @package  WordPress OAuth Integration
 * @since    0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main WordPress OAuth Integration Class.
 *
 * @class WP_OAuth_Integration_Main
 * @version	0.1.0
 */
final class WP_OAuth_Integration_Main {
    
    /**
     * WP_OAuth_Integration_Main version.
     *
     * @var string
     */
    public $version = '0.1.3';

    /**
     * The single instance of the class.
     *
     * @var WP_OAuth_Integration_Main
     * @since 0.1.0
     */
    protected static $_instance = null;
    
    /**
     * Main WP_OAuth_Integration_Main Instance.
     *
     * Ensures only one instance of WP_OAuth_Integration_Main is loaded or can be loaded.
     *
     * @since 0.1.0
     * @return WP_OAuth_Integration_Main - instance.
     */
    public static function instance() {
            if ( is_null( self::$_instance ) ) {
                    self::$_instance = new self();
            }
            return self::$_instance;
    }
    
    /**
     * Cloning is forbidden.
     * 
     */
    private function __clone(){ }
    
    /*
     * Unserializing instances of this class is forbidden.
     */
    private function __wakeup(){ }
    
    /**
    * Constructor.
    */
    public function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
            $this->activate_default_provider();
    }
    
    /**
     * Define Constants.
     */
    private function define_constants() {
        $this->define( 'WOI_ABSPATH', dirname( WP_OAUTH_INTEGRATION_FILE ) . '/' );
        $this->define( 'WOI_PLUGIN_BASENAME', plugin_basename( WP_OAUTH_INTEGRATION_FILE ) );
    }
    
    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
            if ( ! defined( $name ) ) {
                    define( $name, $value );
            }
    }
    
    /**
    * Include required core files used in admin and on the frontend.
    */
    private function includes() {
            require_once WOI_ABSPATH . 'includes/helpers.php';
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-autoloader.php';
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-factory.php';     //Factory        
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-settings.php';    //Settings
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-login.php';       //Login
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-mods.php';        //Custom Mods
            require_once WOI_ABSPATH . 'includes/lib/class-wp-oauth-integration-client.php';      //OAuth2 client to process authentication
    }
    
    public function activate_provider($provider) {                
            if( $provider != false ){
                    WP_OAuth_Integration_Factory::add_provider($provider);
            }
    }
    
    /**
    * Hook into actions and filters.
    *
    * @since 0.1.0
    */
    private function init_hooks() {            
            if ( version_compare( PHP_VERSION, '5.3', 'lt' ) ) {
                    return add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            }

            register_activation_hook(__FILE__, array($this, 'activate'));             

            register_deactivation_hook(__FILE__, array($this, 'deactivate'));

            // Load textdomain
            $this->load_textdomain();
    }
    
    /**
    * Activate default provider
    *
    * @since 0.1.1
    */
    private function activate_default_provider(){
            $provider = 'meetup';

            require_once WOI_ABSPATH . 'includes/wp-'.$provider.'.conf.php';

            $this->activate_provider($provider);

            new WP_OAuth_Integration_Settings($provider);
            new WP_OAuth_Integration_Login($provider);
            new WP_OAuth_Integration_Mods($provider);
    }
    
    /**
     * Do things on plugin activation.
     */
    public function activate() {
        return true;
    }

    /**
     * Called when the plugin is deactivated.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Textdomain.
     *
     * Load the textdomain based on WP language.
     */
    public function load_textdomain() {
            // Load textdomain
            load_plugin_textdomain( 'wp-oauth-integration', false, basename( dirname( WP_OAUTH_INTEGRATION_FILE ) ) . '/languages' );
    }
    
    /**
     * Display PHP 5.3 required notice.
     *
     * Display a notice when the required PHP version is not met.
     *
     * @since 0.1.0
     */
    public function php_version_notice() {
            ?><div class='updated'>
            <p><?php echo sprintf( __( 'WP OAuth Integration requires PHP 5.3 or higher and your current PHP version is %s. Please (contact your host to) update your PHP version.', 'wp-oauth-integration' ), PHP_VERSION ); ?></p>
            </div><?php
    }    
    
    /**
     * Do things on plugin uninstall.
     */
    public function uninstall() {
        if (!current_user_can('activate_plugins')){
            return;
        }
        check_admin_referer('bulk-plugins');

        if (__FILE__ != WP_UNINSTALL_PLUGIN){
            return;
        }
    }

}