<?php
/**
 * Plugin Name: PPC CRM
 * Description: Frontend lead & campaign management for Client and PPC roles.
 * Version:     1.0.0
 * Author:      Your Name
 * Text Domain: ppc-crm
 */

defined( 'ABSPATH' ) || exit;

define( 'PPC_CRM_VERSION', '1.0.0' );
define( 'PPC_CRM_DIR',     plugin_dir_path( __FILE__ ) );
define( 'PPC_CRM_URL',     plugin_dir_url( __FILE__ ) );

// Autoload our classes – simple PSR-4-like loader
// ppc-crm.php
spl_autoload_register( function( $class ) {
    // only load our namespace
    if ( 0 !== strpos( $class, 'PPC_CRM\\' ) ) {
        return;
    }

    // remove namespace prefix
    $relative = substr( $class, strlen( 'PPC_CRM\\' ) );

    // convert backslashes to hyphens, lowercase, prefix with 'class-'
    $filename = 'class-' . strtolower( str_replace( ['\\','_'], ['-','-'], $relative ) ) . '.php';

    $file = PPC_CRM_DIR . 'includes/' . $filename;
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );


// Activation / Deactivation hooks
register_activation_hook( __FILE__,   [ 'PPC_CRM\\Init', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PPC_CRM\\Init', 'deactivate' ] );

// Bootstrap
add_action( 'plugins_loaded', [ 'PPC_CRM\\Init', 'get_instance' ] );
