<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Meta_Boxes {

    public static function register() {
        add_action( 'add_meta_boxes',     [ __CLASS__, 'add_boxes' ] );
        add_action( 'save_post',          [ __CLASS__, 'save_meta' ], 10, 2 );
    }

    public static function add_boxes() {
        $screens = [ 'lead_data', 'campaign_data' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                "{$screen}_details",
                ucfirst( str_replace( '_', ' ', $screen ) ) . ' Details',
                [ __CLASS__, 'render_box' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public static function render_box( $post ) {
        wp_nonce_field( 'ppc_crm_save_' . $post->post_type, 'ppc_crm_nonce' );
        $values = get_post_meta( $post->ID );
        include PPC_CRM_DIR . "templates/{$post->post_type}-meta.php";
    }

    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['ppc_crm_nonce'] ) || ! wp_verify_nonce( $_POST['ppc_crm_nonce'], 'ppc_crm_save_' . $post->post_type ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( 'lead_data' === $post->post_type ) {
            // sanitize and save each field, e.g.:
            if ( isset( $_POST['uid'] ) ) {
                update_post_meta( $post_id, 'uid', sanitize_text_field( $_POST['uid'] ) );
            }
            // ...repeat for all lead fields...
        } elseif ( 'campaign_data' === $post->post_type ) {
            // ...sanitize & save campaign fields...
        }
    }
}
