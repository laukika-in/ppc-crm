<?php
// Uninstall script
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

// Example: maybe drop tables. Disabled by default to protect data.
/*
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lcm_campaigns;" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lcm_leads;" );
*/
?>
