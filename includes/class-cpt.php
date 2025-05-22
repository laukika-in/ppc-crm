<?php
class PPC_CRM_CPT {
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
    }

    public static function register() {
        // Lead Data
        register_post_type( 'lead_data', [
            'labels' => [
                'name'          => __( 'Lead Data', 'ppc-crm' ),
                'singular_name' => __( 'Lead', 'ppc-crm' ),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-leftright',
            'supports'     => [ 'title' ],
            'show_in_rest' => true,
            'capability_type' => [ 'lead_data', 'lead_datas' ],
            'map_meta_cap'    => true,
        ] );

        // Campaign Data
        register_post_type( 'campaign_data', [
            'labels' => [
                'name'          => __( 'Campaign Data', 'ppc-crm' ),
                'singular_name' => __( 'Campaign', 'ppc-crm' ),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-megaphone',
            'supports'     => [ 'title' ],
            'show_in_rest' => true,
            'capability_type' => [ 'campaign_data', 'campaign_datas' ],
            'map_meta_cap'    => true,
        ] );
    }
}