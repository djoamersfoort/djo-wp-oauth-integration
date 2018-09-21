<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//Debug
if (!function_exists('vardump')) {
    function vardump( $string ) {
        var_dump( '<pre>' );
        var_dump( $string );
        var_dump( '</pre>' );
    }
}
