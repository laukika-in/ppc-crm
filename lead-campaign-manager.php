<?php
/*
Plugin Name: Lead Campaign Manager
Plugin URI: https://example.com
Description: Manage Campaign & Lead data with custom tables and roles.
Version: 0.1.0
Author: Your Name
License: GPL2+
Text Domain: lead-campaign-manager
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Autoload core plugin class.
require_once plugin_dir_path( __FILE__ ) . 'core/class-lcm-plugin.php';

// Initialize plugin.
add_action( 'plugins_loaded', array( 'LCM_Plugin', 'instance' ) );
?>
