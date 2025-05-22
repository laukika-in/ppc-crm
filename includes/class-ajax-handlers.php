<?php 
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Ajax_Handlers {

    public static function init() {
        add_action( 'wp_ajax_ppc_crm_save',   [ __CLASS__, 'save_record' ] );
        add_action( 'wp_ajax_ppc_crm_load',   [ __CLASS__, 'load_records' ] );
        add_action( 'wp_ajax_nopriv_ppc_crm_save', '__return_false' );
        add_action( 'wp_ajax_nopriv_ppc_crm_load', '__return_false' );
    }

    public static function save_record() {
        check_ajax_referer( 'ppc_crm_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Unauthorized', 401 );
        }

        $type = sanitize_key( $_POST['type'] ?? '' );
        $data = wp_unslash( $_POST['data'] ?? [] );

        global $wpdb;
        $table = $wpdb->prefix . 'ppc_crm_' . $type . '_data';

        $user_id = get_current_user_id();
        // Client can only save their own records
        if ( in_array( 'client', wp_get_current_user()->roles, true ) ) {
            if ( intval( $data['client_id'] ?? 0 ) !== $user_id ) {
                wp_send_json_error( 'Not allowed', 403 );
            }
        }

        // Prepare data
        $record = [];
        foreach ( $data as $key => $value ) {
            $record[ sanitize_key( $key ) ] = sanitize_text_field( $value );
        }
        $record['client_id'] = intval( $record['client_id'] ?? $user_id );

        if ( ! empty( $record['id'] ) ) {
            $id = intval( $record['id'] );
            unset( $record['id'] );
            $updated = $wpdb->update( $table, $record, [ 'id' => $id ] );
            if ( false === $updated ) {
                wp_send_json_error( 'DB update failed' );
            }
            $insert_id = $id;
        } else {
            unset( $record['id'] );
            $inserted = $wpdb->insert( $table, $record );
            if ( false === $inserted ) {
                wp_send_json_error( 'DB insert failed' );
            }
            $insert_id = $wpdb->insert_id;
        }

        wp_send_json_success( [ 'id' => $insert_id ] );
    }

    public static function load_records() {
        check_ajax_referer( 'ppc_crm_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Unauthorized', 401 );
        }

        $type   = sanitize_key( $_GET['type'] ?? '' );
        $client = intval( $_GET['client'] ?? 0 );

        global $wpdb;
        $table = $wpdb->prefix . 'ppc_crm_' . $type . '_data';

        $where = [];
        if ( in_array( 'client', wp_get_current_user()->roles, true ) ) {
            $where[] = $wpdb->prepare( 'client_id = %d', get_current_user_id() );
        } elseif ( $client && current_user_can( 'ppc_crm_view_all' ) ) {
            $where[] = $wpdb->prepare( 'client_id = %d', $client );
        }

        $sql = "SELECT * FROM {$table}";
        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $results = $wpdb->get_results( $sql, ARRAY_A );
        wp_send_json_success( $results );
    }
}
