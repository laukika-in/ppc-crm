<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Assets {

    public static function enqueue_frontend() {

           // Tabulator from CDN
        wp_enqueue_style(
            'tabulator-cdn',
            'https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css',
            [],
            '6.3.1'
        );
        wp_enqueue_style(
            'ppc-crm-style',
            PPC_CRM_URL . 'assets/css/style.css',
            [],
            PPC_CRM_VERSION
        );
        wp_enqueue_script(
            'tabulator-cdn',
            'https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js',
            [],
            '6.3.1',
            true
        );
        wp_enqueue_script(
            'ppc-crm-tab-config',
            PPC_CRM_URL . 'assets/js/tabulator-config.js',
            [ 'tabulator-cdn' ],
            PPC_CRM_VERSION,
            true
        );
        wp_enqueue_script(
            'ppc-crm-frontend',
            PPC_CRM_URL . 'assets/js/frontend.js',
            [ 'jquery', 'ppc-crm-tab-config' ],
            PPC_CRM_VERSION,
            true
        );
        wp_localize_script( 'ppc-crm-frontend', 'PPC_CRM_Ajax', [
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ppc_crm_nonce' ),
            'role'  => wp_get_current_user()->roles,
        ] );
    }

    public static function enqueue_backend( $hook ) {
        // Only on our CPT screens
        $screen = get_current_screen();
        if ( in_array( $screen->post_type, [ 'lead_data', 'campaign_data' ], true ) ) {
            wp_enqueue_style( 'ppc-crm-style' );
            wp_enqueue_script( 'ppc-crm-backend', PPC_CRM_URL . 'assets/js/backend.js', [ 'jquery' ], PPC_CRM_VERSION, true );
        }
    }
}
