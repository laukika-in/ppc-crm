<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Post_Types {

    public static function register() {
        // Lead Data
        register_post_type( 'lead_data', [
            'label'               => 'Lead Data',
            'public'              => false,
            'show_ui'             => true,
            'capability_type'     => 'lead',
            'map_meta_cap'        => true,
            'supports'            => [ 'title' ],
            'rewrite'             => false,
        ] );

        // Campaign Data
        register_post_type( 'campaign_data', [
            'label'               => 'Campaign Data',
            'public'              => false,
            'show_ui'             => true,
            'capability_type'     => 'campaign',
            'map_meta_cap'        => true,
            'supports'            => [ 'title' ],
            'rewrite'             => false,
        ] );
    }
}
