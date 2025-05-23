<?php
// public/class-ajax.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_lcm_get_leads_json', [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json', [ $this, 'forbid' ] );

		add_action( 'wp_ajax_lcm_create_lead', [ $this, 'create_lead' ] );
		add_action( 'wp_ajax_nopriv_lcm_create_lead', [ $this, 'forbid' ] );
	}

	/* ---------- shared helpers --------------------------------------- */
	private function verify() {
		check_ajax_referer( 'lcm_ajax', 'nonce' );
		if ( ! current_user_can( 'read' ) ) wp_send_json_error( [ 'msg'=>'No permission' ], 403 );
	}
	public function forbid(){ wp_send_json_error( [ 'msg'=>'Login required' ], 401 ); }

	/* ---------- 1) Paginated lead fetch ------------------------------ */
	public function get_leads() {
		$this->verify();
		global $wpdb;

		$page     = max( 1, intval( $_GET['page']     ?? 1 ) );
		$per_page = max( 1, intval( $_GET['per_page'] ?? 10 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}lcm_leads ORDER BY id DESC LIMIT %d OFFSET %d",
			$per_page, $offset
		), ARRAY_A );

		wp_send_json( [ 'total' => $total, 'rows' => $rows ] );
	}

	/* ---------- 2) Create Lead row ----------------------------------- */
	public function create_lead() {

		$this->verify();

		$sanitize = [
			'client_id'              => 'absint',
			'ad_name'                => 'sanitize_text_field',
			'adset'                  => 'sanitize_text_field',
			'uid'                    => 'sanitize_text_field',
			'lead_date'              => 'sanitize_text_field',
			'lead_time'              => 'sanitize_text_field',
			'day'                    => 'sanitize_text_field',
			'phone_number'           => 'sanitize_text_field',
			'alt_number'             => 'sanitize_text_field',
			'email'                  => 'sanitize_email',
			'attempt'                => 'absint',
			'attempt_type'           => 'sanitize_text_field',
			'attempt_status'         => 'sanitize_text_field',
			'store_visit_status'     => 'sanitize_text_field',
			'remarks'                => 'sanitize_text_field',
		];
		$data = [];
		foreach ( $sanitize as $k => $cb ) {
			$data[ $k ] = $cb( $_POST[ $k ] ?? '' );
		}

		if ( ! $data['uid'] || ! $data['adset'] ) {
			wp_send_json_error( [ 'msg'=>'UID and Adset required' ], 400 );
		}

		/* find campaign by Adset title */
		$campaign = get_page_by_title( $data['adset'], OBJECT, 'lcm_campaign' );
		if ( ! $campaign ) wp_send_json_error( [ 'msg'=>'Adset not found' ], 404 );
		$data['campaign_id'] = $campaign->ID;

		/* create wp_post */
		$post_id = wp_insert_post( [
			'post_type'   => 'lcm_lead',
			'post_status' => 'publish',
			'post_title'  => $data['uid'],
		], true );
		if ( is_wp_error( $post_id ) ) wp_send_json_error( [ 'msg'=>$post_id->get_error_message() ], 500 );
		$data['post_id'] = $post_id;

		/* insert into custom table */
		global $wpdb;
		$wpdb->replace( $wpdb->prefix . 'lcm_leads', $data );

		/* recount campaign counters */
		if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
			( new PPC_CRM_Admin_UI )->recount_campaign_counters( $data['adset'] );
		}

		wp_send_json_success();
	}
}
