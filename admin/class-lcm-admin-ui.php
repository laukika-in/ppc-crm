<?php
// admin/class-lcm-admin-ui.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LCM_Admin_UI {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ) );
    }

    public function register_metaboxes() {
        // add_meta_box() calls go here.
    }
}

// Instantiate only for users with appropriate caps.
if ( current_user_can( 'manage_options' ) ) {
    new LCM_Admin_UI();
}
?>
