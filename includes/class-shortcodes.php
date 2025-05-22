<?php
class PPC_CRM_Shortcodes {
    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
        add_shortcode( 'ppc_crm_leads', [ __CLASS__, 'sc_leads' ] );
        add_shortcode( 'ppc_crm_campaigns', [ __CLASS__, 'sc_camps' ] );
    }

    public static function assets() {
        /* Tabulator CDN */
        wp_enqueue_style( 'tabulator', 'https://cdnjs.cloudflare.com/ajax/libs/tabulator/6.2.1/css/tabulator.min.css', [], '6.2.1' );
        wp_enqueue_script( 'tabulator', 'https://cdnjs.cloudflare.com/ajax/libs/tabulator/6.2.1/tabulator.min.js', [], '6.2.1', true );

        /* Plugin styles */
        wp_enqueue_style( 'ppc-crm-front', PPC_CRM_URL . 'assets/css/style.css', [], PPC_CRM_VERSION );
        wp_enqueue_script( 'ppc-crm-front', PPC_CRM_URL . 'assets/js/frontend.js', [ 'tabulator' ], PPC_CRM_VERSION, true );

        wp_localize_script( 'ppc-crm-front', 'PPC_CRM', [
            'nonce'  => wp_create_nonce( 'wp_rest' ),
            'rest'   => [
                'leads' => rest_url( 'ppc-crm/v1/leads' ),
                'camps' => rest_url( 'ppc-crm/v1/campaigns' ),
            ],
            'dropdowns' => PPC_CRM_DROPDOWNS,
        ] );
    }

    public static function sc_leads() {
        return is_user_logged_in() ? '<div id="ppc-crm-leads"></div>' : '<p>Please log in.</p>';
    }
    public static function sc_camps() {
        return is_user_logged_in() ? '<div id="ppc-crm-camps"></div>' : '<p>Please log in.</p>';
    }
}