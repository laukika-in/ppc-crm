<?php
// public/class-ajax.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PPC_CRM_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_lcm_get_leads_json',    [ $this, 'get_leads' ] );
        add_action( 'wp_ajax_lcm_create_lead',       [ $this, 'create_lead' ] );
        add_action( 'wp_ajax_lcm_update_lead',       [ $this, 'update_lead' ] );
        add_action( 'wp_ajax_lcm_delete_lead',       [ $this, 'delete_lead' ] );
        add_action( 'wp_ajax_nopriv_lcm_get_leads_json',  [ $this, 'forbid' ] );
        add_action( 'wp_ajax_nopriv_lcm_create_lead',     [ $this, 'forbid' ] );
        add_action( 'wp_ajax_nopriv_lcm_update_lead',     [ $this, 'forbid' ] );
        add_action( 'wp_ajax_nopriv_lcm_delete_lead',     [ $this, 'forbid' ] );

        add_action( 'wp_ajax_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );
        add_action( 'wp_ajax_lcm_create_campaign',    [ $this, 'create_campaign' ] );
        add_action( 'wp_ajax_lcm_update_campaign',    [ $this, 'update_campaign' ] );
        add_action( 'wp_ajax_lcm_delete_campaign',    [ $this, 'delete_campaign' ] );
    }

    private function verify() {
        check_ajax_referer( 'lcm_ajax', 'nonce' );
        if ( ! current_user_can( 'read' ) ) {
            wp_send_json_error( [ 'msg' => 'No permission' ], 403 );
        }
    }

    public function forbid() {
        wp_send_json_error( [ 'msg' => 'Login required' ], 401 );
    }

    /** Fetch paginated leads with filtering */
    public function get_leads() {
        $this->verify();
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );
        global $wpdb;

        // Base WHERE: client scope
        $client_id = $is_client ? $user->ID : absint( $_GET['client_id'] ?? 0 );
        $where     = $client_id ? $wpdb->prepare( "WHERE client_id = %d", $client_id ) : '';

        // Date range
        if ( ! empty( $_GET['date_from'] ) ) {
            $where .= $wpdb->prepare( " AND lead_date >= %s", sanitize_text_field( $_GET['date_from'] ) );
        }
        if ( ! empty( $_GET['date_to'] ) ) {
            $where .= $wpdb->prepare( " AND lead_date <= %s", sanitize_text_field( $_GET['date_to'] ) );
        }

        // Ad Name & Adset
        if ( ! empty( $_GET['ad_name'] ) ) {
            $where .= $wpdb->prepare( " AND ad_name = %s", sanitize_text_field( $_GET['ad_name'] ) );
        }
        if ( ! empty( $_GET['adset'] ) ) {
            $where .= $wpdb->prepare( " AND adset = %s", sanitize_text_field( $_GET['adset'] ) );
        }

        // Day
        if ( ! empty( $_GET['day'] ) ) {
            $where .= $wpdb->prepare( " AND day = %s", sanitize_text_field( $_GET['day'] ) );
        }

        // Client Type
        if ( ! empty( $_GET['client_type'] ) ) {
            $where .= $wpdb->prepare( " AND client_type = %s", sanitize_text_field( $_GET['client_type'] ) );
        }

        // Source
        if ( ! empty( $_GET['source'] ) ) {
            $where .= $wpdb->prepare( " AND sources = %s", sanitize_text_field( $_GET['source'] ) );
        }

        // Attempt Status
        if ( ! empty( $_GET['attempt_status'] ) ) {
            $where .= $wpdb->prepare( " AND attempt_status = %s", sanitize_text_field( $_GET['attempt_status'] ) );
        }

        // Store Visit Status
        if ( ! empty( $_GET['store_visit_status'] ) ) {
            $where .= $wpdb->prepare( " AND store_visit_status = %s", sanitize_text_field( $_GET['store_visit_status'] ) );
        }

        // Occasion
        if ( ! empty( $_GET['occasion'] ) ) {
            $where .= $wpdb->prepare( " AND occasion = %s", sanitize_text_field( $_GET['occasion'] ) );
        }

        // Text search (name, phone, email)
        if ( ! empty( $_GET['search'] ) ) {
            $s = '%' . $wpdb->esc_like( $_GET['search'] ) . '%';
            $where .= $wpdb->prepare(
                " AND ( name LIKE %s OR phone_number LIKE %s OR email LIKE %s )",
                $s, $s, $s
            );
        }

        // Budget & Product interest
        if ( ! empty( $_GET['budget'] ) ) {
            $b = '%' . $wpdb->esc_like( $_GET['budget'] ) . '%';
            $where .= $wpdb->prepare( " AND budget LIKE %s", $b );
        }
        if ( ! empty( $_GET['product_interest'] ) ) {
            $p = '%' . $wpdb->esc_like( $_GET['product_interest'] ) . '%';
            $where .= $wpdb->prepare( " AND product_looking_to_buy LIKE %s", $p );
        }

        // Pagination
        $page     = max( 1, (int) ( $_GET['page']     ?? 1 ) );
        $per_page = max( 1, (int) ( $_GET['per_page'] ?? 10 ) );
        $offset   = ( $page - 1 ) * $per_page;

        // Total count
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads $where" );

        // Fetch rows
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lcm_leads $where ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ),
            ARRAY_A
        );

        wp_send_json( [ 'total' => $total, 'rows' => $rows ] );
    }

    /** Create a new lead */
    public function create_lead() {
        $this->verify();
        global $wpdb;

        $fields = [
            'client_id','lead_title','ad_name','adset','uid',
            'lead_date','lead_time','day','name','phone_number',
            'alt_number','email','location','client_type',
            'sources','source_of_campaign','targeting_of_campaign',
            'budget','product_looking_to_buy','occasion',
            'attempt','attempt_type','attempt_status',
            'store_visit_status','remarks'
        ];

        $data = [];
        foreach ( $fields as $f ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
        }

        if ( empty( $data['lead_title'] ) ) {
            wp_send_json_error( [ 'msg' => 'Lead Title is required' ], 400 );
        }
        if ( empty( $data['adset'] ) ) {
            wp_send_json_error( [ 'msg' => 'Adset is required' ], 400 );
        }

        // Find campaign link
        $camp = get_page_by_title( $data['adset'], OBJECT, 'lcm_campaign' );
        if ( ! $camp ) {
            wp_send_json_error( [ 'msg' => 'Adset not found' ], 404 );
        }
        $data['campaign_id'] = $camp->ID;

        // Insert WP post
        $post_id = wp_insert_post( [
            'post_type'   => 'lcm_lead',
            'post_status' => 'publish',
            'post_title'  => $data['lead_title'],
        ], true );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'msg' => $post_id->get_error_message() ], 500 );
        }
        $data['post_id'] = $post_id;

        // Force client_id for client role
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );
        if ( $is_client ) {
            $data['client_id'] = $user->ID;
        } else {
            $data['client_id'] = absint( $_POST['client_id'] ?? 0 );
        }

        // Save into custom table
        $wpdb->replace( "{$wpdb->prefix}lcm_leads", $data );

        // Recount campaign tallies
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            ( new PPC_CRM_Admin_UI )->recount_campaign_counters( $data['adset'] );
        }

        wp_send_json_success();
    }

    /** Update an existing lead */
    public function update_lead() {
        $this->verify();
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'msg' => 'Missing lead ID' ], 400 );
        }

        $fields = [
            'client_id','lead_title','ad_name','adset','uid',
            'lead_date','lead_time','day','name','phone_number',
            'alt_number','email','location','client_type',
            'sources','source_of_campaign','targeting_of_campaign',
            'budget','product_looking_to_buy','occasion',
            'attempt','attempt_type','attempt_status',
            'store_visit_status','remarks'
        ];

        $data = [];
        foreach ( $fields as $f ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
        }

        // Force client_id for client role
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );
        if ( $is_client ) {
            $data['client_id'] = $user->ID;
        } else {
            $data['client_id'] = absint( $data['client_id'] );
        }

        // Update custom table row
        $wpdb->update(
            "{$wpdb->prefix}lcm_leads",
            $data,
            [ 'id' => $id ],
            array_fill( 0, count( $data ), '%s' ),
            [ '%d' ]
        );

        // Update WP post title
        $lead_post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
            $id
        ) );
        if ( $lead_post_id ) {
            wp_update_post( [
                'ID'         => $lead_post_id,
                'post_title' => sanitize_text_field( $data['lead_title'] ),
            ] );
        }

        // Recount campaign tallies
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            ( new PPC_CRM_Admin_UI )->recount_campaign_counters( $data['adset'] );
        }

        wp_send_json_success();
    }

    /** Delete a lead */
    public function delete_lead() {
        $this->verify();
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'msg' => 'Missing ID' ], 400 );
        }

        $lead = $wpdb->get_row( $wpdb->prepare(
            "SELECT adset, post_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
            $id
        ), ARRAY_A );
        if ( ! $lead ) {
            wp_send_json_error( [ 'msg' => 'Lead not found' ], 404 );
        }

        // Delete from custom table & WP
        $wpdb->delete( "{$wpdb->prefix}lcm_leads", [ 'id' => $id ] );
        wp_delete_post( $lead['post_id'], true );

        // Recount campaign tallies
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            ( new PPC_CRM_Admin_UI )->recount_campaign_counters( $lead['adset'] );
        }

        // Return new total
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
        wp_send_json_success( [ 'total' => $total ] );
    }

    /** Fetch paginated campaigns with filtering */
    public function get_campaigns() {
        $this->verify();
        global $wpdb;
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );

        // Base WHERE
        $where = 'WHERE 1=1';
        if ( $is_client ) {
            $where .= $wpdb->prepare( " AND client_id = %d", $user->ID );
        } elseif ( ! empty( $_GET['client_id'] ) ) {
            $where .= $wpdb->prepare( " AND client_id = %d", absint( $_GET['client_id'] ) );
        }

        // Month
        if ( ! empty( $_GET['month'] ) ) {
            $where .= $wpdb->prepare( " AND month = %s", sanitize_text_field( $_GET['month'] ) );
        }
        // Location
        if ( ! empty( $_GET['location'] ) ) {
            $where .= $wpdb->prepare(
                " AND location LIKE %s",
                '%' . $wpdb->esc_like( $_GET['location'] ) . '%'
            );
        }
        // Store Visit filter
        if ( ! empty( $_GET['store_visit'] ) ) {
            if ( $_GET['store_visit'] === 'yes' ) {
                $where .= " AND store_visit > 0";
            } else {
                $where .= " AND store_visit = 0";
            }
        }
        // Connected filter
        if ( ! empty( $_GET['has_connected'] ) ) {
            if ( $_GET['has_connected'] === 'yes' ) {
                $where .= " AND connected_number > 0";
            } else {
                $where .= " AND connected_number = 0";
            }
        }

        // Pagination
        $page     = max( 1, (int) ( $_GET['page']     ?? 1 ) );
        $per_page = max( 1, (int) ( $_GET['per_page'] ?? 10 ) );
        $offset   = ( $page - 1 ) * $per_page;

        // Total count
        $table = "{$wpdb->prefix}lcm_campaigns";
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );

        // Fetch rows
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *, not_relevant FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ),
            ARRAY_A
        );

        wp_send_json( [ 'total' => $total, 'rows' => $rows ] );
    }

    /** Create a new campaign */
    public function create_campaign() {
        $this->verify();
        global $wpdb;
        $user      = wp_get_current_user();
        $is_client = in_array( 'client', (array) $user->roles, true );

        $fields = [
            'client_id','campaign_title','campaign_name',
            'month','week','campaign_date','location','adset',
            'leads','reach','impressions','cost_per_lead',
            'amount_spent','cpm','scheduled_store_visit','store_visit'
        ];
        $data = [];
        foreach ( $fields as $f ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
        }

        // Validate title & client
        if ( empty( $data['campaign_title'] ) ) {
            wp_send_json_error( [ 'msg' => 'Campaign Title is required' ], 400 );
        }
        if ( $is_client ) {
            $data['client_id'] = $user->ID;
        } else {
            $data['client_id'] = absint( $data['client_id'] );
            if ( ! $data['client_id'] ) {
                wp_send_json_error( [ 'msg' => 'Client is required' ], 400 );
            }
        }

        // Insert WP post
        $post_id = wp_insert_post( [
            'post_type'   => 'lcm_campaign',
            'post_status' => 'publish',
            'post_title'  => $data['campaign_title'],
        ], true );
        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'msg' => $post_id->get_error_message() ], 500 );
        }
        $data['post_id'] = $post_id;

        // Save to custom table
        $wpdb->replace( "{$wpdb->prefix}lcm_campaigns", $data );
        wp_send_json_success();
    }

    /** Update an existing campaign */
    public function update_campaign() {
        $this->verify();
        global $wpdb;
        $row_id = absint( $_POST['id'] ?? 0 );
        if ( ! $row_id ) {
            wp_send_json_error( [ 'msg' => 'Missing campaign ID' ], 400 );
        }

        $fields = [
            'client_id','campaign_title','campaign_name',
            'month','week','campaign_date','location','adset',
            'leads','reach','impressions','cost_per_lead',
            'amount_spent','cpm','scheduled_store_visit','store_visit'
        ];
        $data = [];
        foreach ( $fields as $f ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
        }

        $is_client = in_array( 'client', (array) wp_get_current_user()->roles, true );
        if ( $is_client ) {
            $data['client_id'] = wp_get_current_user()->ID;
        } else {
            $data['client_id'] = absint( $data['client_id'] );
        }

        // Update custom table
        $table = "{$wpdb->prefix}lcm_campaigns";
        $wpdb->update(
            $table,
            $data,
            [ 'id' => $row_id ],
            array_fill( 0, count( $data ), '%s' ),
            [ '%d' ]
        );

        // Update WP post title
        $post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM $table WHERE id = %d",
            $row_id
        ) );
        if ( $post_id ) {
            wp_update_post( [
                'ID'         => $post_id,
                'post_title' => sanitize_text_field( $_POST['campaign_title'] ?? '' ),
            ] );
        }

        wp_send_json_success();
    }

    /** Delete a campaign */
    public function delete_campaign() {
        $this->verify();
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'msg' => 'Missing id' ], 400 );
        }

        $table = "{$wpdb->prefix}lcm_campaigns";
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT post_id FROM $table WHERE id = %d",
            $id
        ) );
        if ( ! $row ) {
            wp_send_json_error( [ 'msg' => 'Not found' ], 404 );
        }

        $wpdb->delete( $table, [ 'id' => $id ] );
        wp_delete_post( $row->post_id, true );

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        wp_send_json_success( [ 'total' => $total ] );
    }

}
