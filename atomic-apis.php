<?php
/*
Plugin Name: Atomic Smash - APIs
Plugin URI: http://www.atomicsmash.co.uk
Description: Pull from APIs like Twitter
Version: 0.0.6
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

include('apis/twitter.php');
include('apis/instagram.php');

require 'vendor/autoload.php';

$twitter_api = new atomic_api_twitter();
$instagram_api = new atomic_api_instagram();

register_activation_hook( __FILE__, array ( $twitter_api, 'create_table') );
register_deactivation_hook( __FILE__, array ( $twitter_api, 'delete_table') );
