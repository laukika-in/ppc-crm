<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_lcm_get_leads_json',   [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_lcm_create_lead',      [ $this, 'create_lead' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json',  [ $this, 'forbid' ] );
		add_action( 'wp_ajax_nopriv_lcm_create_lead',     [ $this, 'forbid' ] );
	}

	private function verify() {
		check_ajax_referer( 'lcm_ajax', 'nonce' );
		if ( ! current_user_can( 'read' ) ) wp_send_json_error( [ 'msg'=>'No permission' ], 403 );
	}
	public function forbid(){ wp_send_json_error( [ 'msg'=>'Login required' ], 401 ); }

	/* paginated fetch --------------------------------------------------- */
	public function get_leads() {
		$this->verify();
		global $wpdb;

		$p  = max( 1, (int)($_GET['page'] ?? 1) );
		$pp = max( 1, (int)($_GET['per_page'] ?? 10) );
		$o  = ( $p - 1 ) * $pp;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lcm_leads ORDER BY id DESC LIMIT %d OFFSET %d",
				$pp, $o
			),
			ARRAY_A
		);
		wp_send_json( [ 'total'=>$total, 'rows'=>$rows ] );
	}

	/* create lead ------------------------------------------------------- */
	public function create_lead() {

		$this->verify();

		$fields = [
			'client_id','ad_name','adset','uid','lead_date','lead_time','day',
			'phone_number','attempt','attempt_type','attempt_status',
			'store_visit_status','remarks'
		];
		$data=[];
		foreach($fields as $f){ $data[$f]=sanitize_text_field($_POST[$f]??''); }

		if(!$data['uid']||!$data['adset']) wp_send_json_error(['msg'=>'UID & Adset required'],400);

		$camp=get_page_by_title($data['adset'],OBJECT,'lcm_campaign');
		if(!$camp) wp_send_json_error(['msg'=>'Adset not found'],404);
		$data['campaign_id']=$camp->ID;

		$post_id=wp_insert_post([
			'post_type'=>'lcm_lead','post_status'=>'publish','post_title'=>$data['uid']
		],true);
		if(is_wp_error($post_id)) wp_send_json_error(['msg'=>$post_id->get_error_message()],500);
		$data['post_id']=$post_id;

		global $wpdb;
		$wpdb->replace($wpdb->prefix.'lcm_leads',$data);

		if(class_exists('PPC_CRM_Admin_UI')){
			(new PPC_CRM_Admin_UI)->recount_campaign_counters($data['adset']);
		}
		wp_send_json_success();
	}
}
