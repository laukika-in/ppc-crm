<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Access_Control {

    public static function init() {
        // Prevent wp-admin access for non-admins
        add_action( 'admin_init', [ __CLASS__, 'maybe_block_admin' ] );
        // Map meta capabilities for our CPTs
        add_filter( 'map_meta_cap', [ __CLASS__, 'map_meta_capabilities' ], 10, 4 );
    }

    public static function maybe_block_admin() {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) && ! current_user_can( 'manage_options' ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * Grant or deny edit/read/delete based on ownership and role.
     */
    public static function map_meta_capabilities( $caps, $cap, $user_id, $args ) {
        // Only intervene on post-level caps
        if ( in_array( $cap, [ 'edit_post', 'delete_post', 'read_post' ], true ) && ! empty( $args[0] ) ) {
            $post = get_post( $args[0] );
            if ( $post && in_array( $post->post_type, [ 'lead_data', 'campaign_data' ], true ) ) {
                $user = get_userdata( $user_id );

                // PPC role: allow all
                if ( in_array( 'ppc', (array) $user->roles, true ) && current_user_can( 'ppc_crm_view_all' ) ) {
                    return [ 'exist' ];
                }

                // Client role: only own
                if ( in_array( 'client', (array) $user->roles, true ) ) {
                    if ( intval( $post->post_author ) === $user_id ) {
                        return [ 'exist' ];
                    }
                    return [ 'do_not_allow' ];
                }
            }
        }

        return $caps;
    }
}

// Hook it up
add_action( 'init', [ Access_Control::class, 'init' ] );
