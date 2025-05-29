<?php
/**
 * Plugin Name:       PPC-CRM
 * Description:       Campaign + Lead manager with custom tables and no-admin access for Clients/PPC.
 * Plugin URI:        https://laukika.com/
 * Author:            Laukika
<<<<<<< HEAD
 * Version:           0.3.69
=======
 * Version:           0.3.61
>>>>>>> parent of db4a57c (Update ppc-crm.php)
 * Author URI:        https://laukika.com/
 * Text Domain:       ppc-crm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'PPC_CRM_VERSION' ) ) {
	$plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version' ] );
	define( 'PPC_CRM_VERSION', $plugin_data['Version'] );
}
/** Core singleton */
require_once plugin_dir_path( __FILE__ ) . 'core/class-plugin.php';

/** Admin UI (metaboxes etc.) */
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-ui.php';
}

/** Bootstrap */
add_action( 'plugins_loaded', [ 'PPC_CRM_Plugin', 'instance' ] );

/** Lifecycle hooks */
register_activation_hook( __FILE__,   [ 'PPC_CRM_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PPC_CRM_Plugin', 'deactivate' ] );
