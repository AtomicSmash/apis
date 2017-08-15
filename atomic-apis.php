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
// add_action( 'admin_menu', 'baseMenuPage' );


// require 'vendor/autoload.php';
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

include('apis/twitter.php');
// include('apis/instagram.php');


$twitterAPI = new atomic_api();

register_activation_hook( __FILE__, array ( $twitterAPI, 'create_table') );
register_deactivation_hook( __FILE__, array ( $twitterAPI, 'delete_table') );
