<?php
/*
Plugin Name: Atomic Smash - APIs
Plugin URI: http://www.atomicsmash.co.uk
Description: Pull from APIs like Twitter and Instagram
Version: 0.1.0
Author: Atomic Smash
Author URI: n/a
*/

global $action, $wpdb;

function register_session(){
    if( !session_id() )
        session_start();
}
add_action('init','register_session');

// require 'vendor/autoload.php';
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

include('apis/atomic_api_base.php');
include('apis/twitter.php');
include('apis/instagram.php');
// require 'vendor/autoload.php';


// if ( defined( 'WP_CLI' ) && WP_CLI ) {
//     /**
//      * Grab info from APIs
//      *
//      * wp atomicsmash create_dates_varient todayÊ
//      */
//     class AS_API_CLI extends WP_CLI_Command {};
//
//     WP_CLI::add_command( 'APIs', 'AS_API_CLI' );
//
// }


// if( defined('TWITTER_CONSUMER_KEY') && TWITTER_CONSUMER_KEY != "" ){
$twitter_api = new atomic_api_twitter();
register_activation_hook( __FILE__, array ( $twitter_api, 'create_table') );
// register_deactivation_hook( __FILE__, array ( $twitter_api, 'delete_table') );
// };


$insta_variables = [
    'name' => 'Instagram',
    'db_table' => $wpdb->prefix . 'api_instagram',
    'columns' => array(
        'thumbnail'    => 'Thumbnail',
        'caption'      => 'Caption',
        'added'      => 'Added'
    )
];



// if( defined('INSTAGRAM_ACCESS_TOKEN') && INSTAGRAM_ACCESS_TOKEN != "" ){
$instagram_api = new atomic_api_instagram($insta_variables);
register_activation_hook( __FILE__, array ( $instagram_api, 'create_table') );
// register_deactivation_hook( __FILE__, array ( $instagram_api, 'delete_table') );
// };
