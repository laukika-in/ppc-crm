<?php
// public/class-ajax.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );

		add_action( 'wp_ajax_lcm_get_leads_json', [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json', [ $this, 'get_leads' ] );

		add_action( 'wp_ajax_lcm_create_lead', [ $this, 'create_lead' ] );
		add_action( 'wp_ajax_nopriv_lcm_create_lead', [ $this, 'forbid' ] ); // guests blocked
	}

	/* ---------- helpers ------------------------------------------------ */
	private function verify() {
		check_ajax_referer( 'lcm_ajax', 'nonce' );
		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'msg' => 'No permission' ], 403 );
		}
	}
	public function forbid(){ wp_send_json_error( [ 'msg'=>'Login required' ], 401 ); }

	/* ---------- fetchers ----------------------------------------------- */
	public function get_campaigns() {
		$this->verify();
		global $wpdb;
		wp_send_json( $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}lcm_campaigns ORDER BY id DESC", ARRAY_A ) );
	}

	public function get_leads() {
		$this->verify();
		global $wpdb;
		wp_send_json( $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}lcm_leads ORDER BY id DESC", ARRAY_A ) );
	}

	/* ---------- create lead ------------------------------------------- */
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
			'name'                   => 'sanitize_text_field',
			'phone_number'           => 'sanitize_text_field',
			'alt_number'             => 'sanitize_text_field',
			'email'                  => 'sanitize_email',
			'location'               => 'sanitize_text_field',
			'client_type'            => 'sanitize_text_field',
			'sources'                => 'sanitize_text_field',
			'source_of_campaign'     => 'sanitize_text_field',
			'targeting_of_campaign'  => 'sanitize_text_field',
			'budget'                 => 'sanitize_text_field',
			'product_looking_to_buy' => 'sanitize_text_field',
			'occasion'               => 'sanitize_text_field',
			'for_whom'               => 'sanitize_text_field',
			'final_type'             => 'sanitize_text_field',
			'final_sub_type'         => 'sanitize_text_field',
			'main_city'              => 'sanitize_text_field',
			'store_location'         => 'sanitize_text_field',
			'store_visit'            => 'sanitize_text_field',
			'store_visit_status'     => 'sanitize_text_field',
			'attempt'                => 'absint',
			'attempt_type'           => 'sanitize_text_field',
			'attempt_status'         => 'sanitize_text_field',
			'remarks'                => 'sanitize_text_field',
		];
		$data = [];
		foreach ( $sanitize as $k => $cb ) {
			$val = $_POST[ $k ] ?? '';
			$data[ $k ] = $cb ? $cb( $val ) : $val;
		}

		if ( ! $data['uid'] || ! $data['adset'] ) {
			wp_send_json_error( [ 'msg'=>'UID and Adset required' ], 400 );
		}

		/* find campaign */
		$campaign = get_page_by_title( $data['adset'], OBJECT, 'lcm_campaign' );
		if ( ! $campaign ) wp_send_json_error( [ 'msg'=>'Adset not in Campaigns' ], 404 );
		$data['campaign_id'] = $campaign->ID;

		/* insert wp_post */
		$post_id = wp_insert_post( [
			'post_type'   => 'lcm_lead',
			'post_status' => 'publish',
			'post_title'  => $data['uid'],
		], true );
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [ 'msg'=>$post_id->get_error_message() ], 500 );
		}
		$data['post_id'] = $post_id;

		/* insert custom-table row */
		global $wpdb;
		$wpdb->replace( $wpdb->prefix . 'lcm_leads', $data );

		/* refresh campaign counters */
		if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
			( new PPC_CRM_Admin_UI )->recount_campaign_counters( $data['adset'] );
		}

		wp_send_json_success();
	}
}
