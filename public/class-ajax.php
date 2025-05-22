<?php
// public/class-ajax.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {

		add_action( 'wp_ajax_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );

		add_action( 'wp_ajax_lcm_get_leads_json', [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json', [ $this, 'get_leads' ] );
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
}
