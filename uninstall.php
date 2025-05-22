<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lcm_campaigns;" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lcm_leads;" );
