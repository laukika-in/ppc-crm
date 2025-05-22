<?php
/**
 * Plugin Name: PPC CRM
 * Description: Front‑end lead & campaign management (Tabulator tables) for PPC & Client roles.
 * Version:     1.1.0
 * Author:      Your Name
 * Text Domain: ppc-crm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// Constants
// -----------------------------------------------------------------------------

define( 'PPC_CRM_VERSION', '1.1.0' );
define( 'PPC_CRM_FILE', __FILE__ );
define( 'PPC_CRM_DIR', plugin_dir_path( __FILE__ ) );
define( 'PPC_CRM_URL', plugin_dir_url( __FILE__ ) );

define( 'PPC_CRM_DROPDOWNS', include PPC_CRM_DIR . 'data/dropdown.php' );

// -----------------------------------------------------------------------------
// PSR‑4‑like autoloader for /includes classes
// -----------------------------------------------------------------------------

spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'PPC_CRM_' ) !== 0 ) {
        return;
    }
    $path = PPC_CRM_DIR . 'includes/' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
    if ( file_exists( $path ) ) {
        require_once $path;
    }
});

// -----------------------------------------------------------------------------
// Bootstrap plugin after all plugins loaded
// -----------------------------------------------------------------------------

add_action( 'plugins_loaded', function () {
    PPC_CRM_Roles::init();
    PPC_CRM_CPT::init();
    PPC_CRM_Meta::init();
    PPC_CRM_REST::init();
    PPC_CRM_Shortcodes::init();
});

register_activation_hook( __FILE__, [ 'PPC_CRM_Roles', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PPC_CRM_Roles', 'deactivate' ] );