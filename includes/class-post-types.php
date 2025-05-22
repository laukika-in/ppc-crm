<?php
// File: ppc-crm/includes/class-post-types.php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Post_Types {

    /**
     * Register custom post types: lead_data and campaign_data
     */
    public static function register() {
        // Lead Data CPT
        register_post_type( 'lead_data', [
            'labels'             => [
                'name'          => __( 'Lead Data', 'ppc-crm' ),
                'singular_name' => __( 'Lead',      'ppc-crm' ),
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'lead',
            'map_meta_cap'       => true,
            'supports'           => [ 'title' ],
            'has_archive'        => false,
            'rewrite'            => false,
        ] );

        // Campaign Data CPT
        register_post_type( 'campaign_data', [
            'labels'             => [
                'name'          => __( 'Campaign Data', 'ppc-crm' ),
                'singular_name' => __( 'Campaign',      'ppc-crm' ),
            ],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'campaign',
            'map_meta_cap'       => true,
            'supports'           => [ 'title' ],
            'has_archive'        => false,
            'rewrite'            => false,
        ] );
    }
}
