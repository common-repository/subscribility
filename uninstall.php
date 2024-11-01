<?php

/**
 *
 * This file will be executed during plugin uninstall process.
 *
 * See: https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 */

// die when the file is called directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

$logs_files = dirname(__FILE__) . "/logs/";
delete_files($logs_files);

/*
 * php delete function that deals with directories recursively
 */
function delete_files($target) {
    if(is_dir($target)){
        $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

        foreach( $files as $file ){
            delete_files( $file );
        }

        rmdir( $target );
    } elseif(is_file($target)) {
        unlink( $target );
    }
}

// Clear stream cron job
wp_clear_scheduled_hook( 'wp99234_send_stream_action' );

if ( !is_multisite() ) {
    $table_name_troly_streams = $wpdb->prefix . "troly_streams";
    $sql_troly_streams = "DROP TABLE " . $table_name_troly_streams;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $wpdb->get_results( $sql_troly_streams );
} else {
    $query   = "SELECT blog_id FROM `" . $wpdb->prefix . "blogs`";
    $results = $wpdb->get_results( $query );

    foreach( $results as $key => $value ) {
        $table_name_troly_streams = $wpdb->prefix . $value->blog_id . "_" . "troly_streams";
        $sql_troly_streams = "DROP TABLE " . $table_name_troly_streams;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_troly_streams );
    }
}
