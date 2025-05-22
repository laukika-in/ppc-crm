<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Ajax_Handlers {

    public static function init() {
        add_action( 'wp_ajax_ppc_crm_save',   [ __CLASS__, 'save_record' ] );
        add_action( 'wp_ajax_nopriv_ppc_crm_save', '__return_null' ); // no public
        add_action( 'wp_ajax_ppc_crm_load',   [ __CLASS__, 'load_records' ] );
    }

    public static function save_record() {
        check_ajax_referer( 'ppc_crm_nonce', 'nonce' );

        $type = sanitize_key( $_POST['type'] );
        $data = wp_unslash( $_POST['data'] ); // array of field => value

        // Capability check
        if ( 'lead' === $type && ! current_user_can( 'ppc_crm_edit_' . ( current_user_can( 'ppc_crm_edit_all' ) ? 'all' : 'own' ) ) ) {
            wp_send_json_error( 'Not allowed' );
        }

        // Create or update post
        $postarr = [
            'ID'         => intval( $data['id'] ?? 0 ),
            'post_type'  => $type . '_data',
            'post_title' => sanitize_text_field( $data['uid'] ?? '' ),
            'post_status'=> 'publish',
        ];
        $post_id = wp_insert_post( $postarr, true );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }

        // Save meta
        foreach ( $data as $key => $value ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
        }

        wp_send_json_success( [ 'id' => $post_id ] );
    }

    public static function load_records() {
        check_ajax_referer( 'ppc_crm_nonce', 'nonce' );
        $type   = sanitize_key( $_GET['type'] );
        $client = absint( $_GET['client'] ?? 0 );

        $args = [
            'post_type'   => $type . '_data',
            'post_status' => 'publish',
            'numberposts' => -1,
        ];

        // Scope for client role
        if ( in_array( 'client', wp_get_current_user()->roles, true ) ) {
            $args['meta_query'] = [
                [
                    'key'   => 'client',
                    'value' => get_current_user_id(),
                ],
            ];
        } elseif ( 'lead' === $type && $client && current_user_can( 'ppc_crm_view_all' ) ) {
            $args['meta_query'] = [
                [ 'key' => 'client', 'value' => $client ],
            ];
        }

        $posts = get_posts( $args );
        $data  = [];
        foreach ( $posts as $p ) {
            $meta = get_post_meta( $p->ID );
            $row  = array_map( 'maybe_unserialize', $meta );
            $row['id'] = $p->ID;
            $data[]   = $row;
        }
        wp_send_json_success( $data );
    }
}
