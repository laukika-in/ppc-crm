<?php
// public/class-ajax.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );

		add_action( 'wp_ajax_lcm_get_leads_json', [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json', [ $this, 'get_leads' ] );
        
    add_action( 'wp_ajax_lcm_create_lead',       [ $this, 'create_lead' ] );
    add_action( 'wp_ajax_nopriv_lcm_create_lead',[ $this, 'forbid' ] ); // guests blocked
	}

	/* --------------------------------------------------------------- */
	private function verify() {
		check_ajax_referer( 'lcm_ajax', 'nonce' );
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied' ], 403 );
		}
	}

	public function get_campaigns() {

		$this->verify();

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}lcm_campaigns ORDER BY id DESC",
			ARRAY_A
		);

		wp_send_json( $rows );
	}

	public function get_leads() {

		$this->verify();

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}lcm_leads ORDER BY id DESC",
			ARRAY_A
		);

		wp_send_json( $rows );
	}
    public function create_lead() {

    $this->verify();                                        // nonce + caps

    /* Basic required fields */
    $adset         = sanitize_text_field( $_POST['adset']      ?? '' );
    $uid           = sanitize_text_field( $_POST['uid']        ?? '' );
    $attempt       = intval( $_POST['attempt']                 ?? 0 );
    $attempt_type  = sanitize_text_field( $_POST['attempt_type']  ?? '' );
    $attempt_status= sanitize_text_field( $_POST['attempt_status']?? '' );
    $store_status  = sanitize_text_field( $_POST['store_status']  ?? '' );

    if ( ! $adset || ! $uid ) {
        wp_send_json_error( [ 'msg' => 'Missing UID or Adset' ], 400 );
    }

    /* Find Campaign post by title (adset) */
    $campaign = get_page_by_title( $adset, OBJECT, 'lcm_campaign' );
    if ( ! $campaign ) {
        wp_send_json_error( [ 'msg' => 'Adset not found in Campaigns' ], 404 );
    }

    /* Build post */
    $post_id = wp_insert_post( [
        'post_type'   => 'lcm_lead',
        'post_status' => 'publish',
        'post_title'  => $uid,
    ], true );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( [ 'msg' => $post_id->get_error_message() ], 500 );
    }

    /* Minimal data for recount */
    global $wpdb;
    $wpdb->replace( $wpdb->prefix . 'lcm_leads', [
        'post_id'          => $post_id,
        'campaign_id'      => $campaign->ID,
        'ad_name'          => $campaign->post_title,
        'adset'            => $adset,
        'uid'              => $uid,
        'lead_date'        => current_time( 'mysql' ),
        'lead_time'        => current_time( 'H:i:s' ),
        'day'              => current_time( 'l' ),
        'attempt'          => $attempt,
        'attempt_type'     => $attempt_type,
        'attempt_status'   => $attempt_status,
        'store_visit_status'=> $store_status,
    ] );

    /* Re-count campaign counters */
    if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
        ( new PPC_CRM_Admin_UI )->recount_campaign_counters( $adset );
    }

    wp_send_json_success( [ 'msg' => 'Lead added' ] );
}

/* Guests shortcut */
private function forbid(){ wp_send_json_error( [ 'msg'=>'Login required' ], 401 ); }
}
