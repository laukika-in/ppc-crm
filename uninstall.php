<?php
/**
 * Clean up on uninstall: drop custom tables, delete options, etc.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
// Remove roles
remove_role( 'client' );
remove_role( 'ppc' );
// Additional cleanup here...
