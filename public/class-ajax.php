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

	$page     = max( 1, intval( $_GET['page'] ?? 1 ) );
	$per_page = max( 1, intval( $_GET['per_page'] ?? 10 ) );
	$offset   = ( $page - 1 ) * $per_page;

	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );

	$rows  = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}lcm_leads
		  ORDER BY id DESC
		  LIMIT %d OFFSET %d",
		$per_page, $offset
	), ARRAY_A );

	wp_send_json( [
		'total' => $total,
		'rows'  => $rows,
	] );
}

}
