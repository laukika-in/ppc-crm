<?php
/**
 * Plugin Name: PPC CRM
 * Description: Custom plugin for Lead and Campaign Data Management with role-based access and Tabulator.js frontend.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: ppc-crm
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin paths
define( 'PPC_CRM_PATH', plugin_dir_path( __FILE__ ) );
define( 'PPC_CRM_URL',  plugin_dir_url( __FILE__ ) );

// Require core classes
require_once PPC_CRM_PATH . 'includes/class-init.php';
require_once PPC_CRM_PATH . 'includes/class-post-types.php';
require_once PPC_CRM_PATH . 'includes/class-user-roles.php';
require_once PPC_CRM_PATH . 'includes/class-meta-boxes.php';
require_once PPC_CRM_PATH . 'includes/class-shortcodes.php';
require_once PPC_CRM_PATH . 'includes/class-access-control.php';
require_once PPC_CRM_PATH . 'includes/class-ajax-handlers.php';
require_once PPC_CRM_PATH . 'includes/class-admin-ui.php';

// Initialize plugin
add_action( 'plugins_loaded', array( 'PPC_CRM\Init', 'run' ) );