<?php
class PPC_CRM_Meta {
    // condensed field keys (labels handled by Tabulator headings or i18n)
    public static $lead_fields = [
        'client', 'uid', 'lead_date', 'lead_time', 'day', 'name', 'phone', 'alt_phone', 'email', 'location', 'client_type', 'sources', 'campaign_source', 'campaign_target', 'ad_name', 'adset', 'budget', 'product', 'occasion', 'for_whom', 'final_type', 'final_sub_type', 'main_city', 'store_location', 'store_visit', 'store_visit_status', 'attempt', 'attempt_type', 'attempt_status', 'remarks'
    ];

    public static $camp_fields = [
        'client', 'month', 'week', 'date', 'location', 'campaign_name', 'adset', 'leads', 'reach', 'impressions', 'cpl', 'amount_spent', 'cpm', 'connected', 'not_connected', 'relevant', 'na', 'scheduled_store_visit', 'store_visit'
    ];

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_meta' ] );
    }

    public static function register_meta() {
        $common = [
            'single'       => true,
            'show_in_rest' => true,
            'type'         => 'string',
            'auth_callback' => function() { return current_user_can( 'read' ); },
        ];

        foreach ( self::$lead_fields as $f ) {
            register_post_meta( 'lead_data', $f, $common );
        }
        foreach ( self::$camp_fields as $f ) {
            register_post_meta( 'campaign_data', $f, $common );
        }
    }
}