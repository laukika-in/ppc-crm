<?php
class PPC_CRM_REST {
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
    }

    private static function ok() { return current_user_can( 'read' ); }

    public static function routes() {
        register_rest_route( 'ppc-crm/v1', '/leads', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_leads' ],
            'permission_callback' => [ __CLASS__, 'ok' ],
        ] );
        register_rest_route( 'ppc-crm/v1', '/leads/(?P<id>\\d+)', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'save_lead' ],
            'permission_callback' => [ __CLASS__, 'ok' ],
            'args' => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );
        register_rest_route( 'ppc-crm/v1', '/campaigns', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_camps' ],
            'permission_callback' => [ __CLASS__, 'ok' ],
        ] );
        register_rest_route( 'ppc-crm/v1', '/campaigns/(?P<id>\\d+)', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'save_camp' ],
            'permission_callback' => [ __CLASS__, 'ok' ],
            'args' => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );
    }

    // helpers
    private static function filter_for_client( array $posts ) {
        $u = wp_get_current_user();
        if ( in_array( 'client', $u->roles, true ) ) {
            return array_filter( $posts, function ( $p ) use ( $u ) {
                return (string) get_post_meta( $p->ID, 'client', true ) === (string) $u->ID;
            } );
        }
        return $posts;
    }

    // Lead handlers
    public static function get_leads() {
        $posts = self::filter_for_client( get_posts( [ 'post_type' => 'lead_data', 'posts_per_page' => -1 ] ) );
        return rest_ensure_response( array_map( [ __CLASS__, 'map_lead' ], $posts ) );
    }

    private static function map_lead( $p ) {
        $row = [ 'id' => $p->ID ];
        foreach ( PPC_CRM_Meta::$lead_fields as $f ) {
            $row[ $f ] = get_post_meta( $p->ID, $f, true );
        }
        return $row;
    }

    public static function save_lead( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $body = $req->get_json_params();
        foreach ( PPC_CRM_Meta::$lead_fields as $f ) {
            if ( isset( $body[ $f ] ) ) {
                update_post_meta( $id, $f, sanitize_text_field( $body[ $f ] ) );
            }
        }
        return [ 'success' => true ];
    }

    // Campaign handlers
    public static function get_camps() {
        $posts = self::filter_for_client( get_posts( [ 'post_type' => 'campaign_data', 'posts_per_page' => -1 ] ) );
        return rest_ensure_response( array_map( [ __CLASS__, 'map_camp' ], $posts ) );
    }

    private static function map_camp( $p ) {
        $row = [ 'id' => $p->ID ];
        foreach ( PPC_CRM_Meta::$camp_fields as $f ) {
            $row[ $f ] = get_post_meta( $p->ID, $f, true );
        }
        return $row;
    }

    public static function save_camp( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $body = $req->get_json_params();
        foreach ( PPC_CRM_Meta::$camp_fields as $f ) {
            if ( isset( $body[ $f ] ) ) {
                update_post_meta( $id, $f, sanitize_text_field( $body[ $f ] ) );
            }
        }
        return [ 'success' => true ];
    }
}