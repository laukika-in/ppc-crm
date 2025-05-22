<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Access_Control {

    public static function init() {
        // Prevent wp-admin access
        add_action( 'admin_init', [ __CLASS__, 'block_admin_for_roles' ] );

        // Map capabilities
        add_filter( 'user_has_cap', [ __CLASS__, 'grant_caps' ], 10, 4 );
    }

    public static function block_admin_for_roles() {
        if ( ! current_user_can( 'manage_options' ) && is_admin() && ! defined( 'DOING_AJAX' ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }

    public static function grant_caps( $allcaps, $caps, $args, $user ) {
        // Allow clients to edit only their own posts
        if ( in_array( 'client', (array) $user->roles, true ) ) {
            $allcaps['edit_lead']     = false;
            $allcaps['delete_lead']   = false;
            $allcaps['edit_campaign'] = false;
            // intercept map_meta_cap to allow edit_own etc.
        }
        return $allcaps;
    }
}
