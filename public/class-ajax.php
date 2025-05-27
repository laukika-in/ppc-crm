<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_lcm_get_leads_json',   [ $this, 'get_leads' ] );
		add_action( 'wp_ajax_lcm_create_lead',      [ $this, 'create_lead' ] );
		add_action( 'wp_ajax_nopriv_lcm_get_leads_json',  [ $this, 'forbid' ] );
		add_action( 'wp_ajax_nopriv_lcm_create_lead',     [ $this, 'forbid' ] );
        add_action( 'wp_ajax_lcm_delete_lead', [ $this, 'delete_lead' ] );
add_action( 'wp_ajax_nopriv_lcm_delete_lead', [ $this, 'forbid' ] );
add_action( 'wp_ajax_lcm_get_campaigns_json', [ $this, 'get_campaigns' ] );
add_action( 'wp_ajax_lcm_create_campaign',    [ $this, 'create_campaign' ] );
add_action( 'wp_ajax_lcm_delete_campaign',    [ $this, 'delete_campaign' ] );
add_action( 'wp_ajax_lcm_update_lead',     [ $this, 'update_lead' ] );
add_action( 'wp_ajax_lcm_update_campaign', [ $this, 'update_campaign' ] );

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

    $user = wp_get_current_user();
    $is_client = in_array( 'client', (array) $user->roles, true );

    $client_id = $is_client ? $user->ID : absint( $_GET['client_id'] ?? 0 );

    $where = $client_id ? $wpdb->prepare( "WHERE client_id = %d", $client_id ) : '';

    $p  = max(1,(int)($_GET['page']??1));
    $pp = max(1,(int)($_GET['per_page']??10));
    $o  = ($p-1)*$pp;

    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads $where" );
    $rows  = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lcm_leads $where ORDER BY id DESC LIMIT %d OFFSET %d",
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
if ( $is_client ) {
    $data['client_id'] = $user->ID;          // force client id
} else {
    $data['client_id'] = absint( $_POST['client_id'] ?? 0 );
}

		global $wpdb;
		$wpdb->replace($wpdb->prefix.'lcm_leads',$data);

		if(class_exists('PPC_CRM_Admin_UI')){
			(new PPC_CRM_Admin_UI)->recount_campaign_counters($data['adset']);
		}
		wp_send_json_success();
	}
    /* ---------- 3) Delete saved lead row ------------------------------- */
public function delete_lead() {

	$this->verify();

	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id ) wp_send_json_error( [ 'msg' => 'Missing ID' ], 400 );

	global $wpdb;
	$lead = $wpdb->get_row( $wpdb->prepare(
		"SELECT adset, post_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
		$id
	), ARRAY_A );

	if ( ! $lead ) wp_send_json_error( [ 'msg' => 'Lead not found' ], 404 );

	/* Remove from custom table and wp_posts */
	$wpdb->delete( $wpdb->prefix . 'lcm_leads', [ 'id' => $id ] );
	wp_delete_post( $lead['post_id'], true );

	/* Re-count campaign counters */
	if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
		( new PPC_CRM_Admin_UI )->recount_campaign_counters( $lead['adset'] );
	}
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
wp_send_json_success( [ 'total' => $total ] );   // ← NEW
 
}
/* ---------- Campaign: fetch --------------------------------------- */
public function get_campaigns() {
    $this->verify();
    global $wpdb;

    $user_id = get_current_user_id();
    $is_client = current_user_can('client');

    $p  = max(1, (int)($_GET['page'] ?? 1));
    $pp = max(1, (int)($_GET['per_page'] ?? 10));
    $o  = ($p - 1) * $pp;

    $table = $wpdb->prefix . 'lcm_campaigns';
    $where = 'WHERE 1=1';

    // Filter by role
    if ($is_client) {
        $where .= $wpdb->prepare(" AND client_id = %d", $user_id);
    } elseif (!empty($_GET['client_id'])) {
        $where .= $wpdb->prepare(" AND client_id = %d", absint($_GET['client_id']));
    }

    // Total count
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

    // Campaign rows
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d", $pp, $o),
        ARRAY_A
    );

    wp_send_json(['total' => $total, 'rows' => $rows]);
}


/* ---------- Campaign: create / update ----------------------------- */
public function create_campaign() {
    $this->verify();

    $user   = wp_get_current_user();
    $is_client = in_array('client', (array) $user->roles);
    $fields = [
        'client_id', 'month', 'week', 'campaign_date', 'location', 'adset',
        'leads', 'reach', 'impressions', 'cost_per_lead', 'amount_spent', 'cpm',
        'connected_number', 'not_connected', 'relevant', 'not_available',
        'scheduled_store_visit', 'store_visit'
    ];

    $data = [];
    foreach ($fields as $f) {
        $data[$f] = sanitize_text_field($_POST[$f] ?? '');
    }

    // Title = adset
    $title = $data['adset'] ?? '';
    if (!$title) wp_send_json_error(['msg' => 'Adset required'], 400);

    // Force client ID for client role
    if ($is_client) {
        $data['client_id'] = $user->ID;
    } else {
        $data['client_id'] = absint($data['client_id']);
        if (!$data['client_id']) {
            wp_send_json_error(['msg' => 'Client is required'], 400);
        }
    }

    // Insert post (for linking)
    $post_id = wp_insert_post([
        'post_type'   => 'lcm_campaign',
        'post_status' => 'publish',
        'post_title'  => $title
    ], true);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['msg' => $post_id->get_error_message()], 500);
    }

    $data['post_id'] = $post_id;

    global $wpdb;
    $wpdb->replace($wpdb->prefix . 'lcm_campaigns', $data);

    wp_send_json_success();
}


/* ---------- Campaign: delete -------------------------------------- */
public function delete_campaign(){
	$this->verify();
	$id=absint($_POST['id']??0);
	if(!$id) wp_send_json_error(['msg'=>'Missing id'],400);

	global $wpdb;
	$row=$wpdb->get_row($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}lcm_campaigns WHERE id=%d",$id));
	if(!$row) wp_send_json_error(['msg'=>'Not found'],404);

	$wpdb->delete($wpdb->prefix.'lcm_campaigns',['id'=>$id]);
	wp_delete_post($row->post_id,true);

	$total=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}lcm_campaigns");
	wp_send_json_success(['total'=>$total]);
}
public function update_lead() {

	$this->verify();
	global $wpdb;

	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id ) wp_send_json_error( [ 'msg'=>'Missing id' ], 400 );

	$cols = [
		'ad_name','adset','lead_date','lead_time','day','phone_number',
		'attempt','attempt_type','attempt_status','store_visit_status','remarks'
	];
	$data = [];
	foreach ( $cols as $c ) {
		if ( isset( $_POST[$c] ) ) $data[$c] = sanitize_text_field( $_POST[$c] );
	}
	if ( empty( $data ) ) wp_send_json_success();  // nothing to update

	$wpdb->update( $wpdb->prefix.'lcm_leads', $data, [ 'id'=>$id ] );
	wp_send_json_success();
}
public function update_campaign() {

	$this->verify();
	global $wpdb;

	$id = absint( $_POST['id'] ?? 0 );
	if ( ! $id ) wp_send_json_error( [ 'msg'=>'Missing id' ], 400 );

	$cols = [
		'month','week','campaign_date','location','leads','reach','impressions',
		'cost_per_lead','amount_spent','cpm'
	];
	$data = [];
	foreach ( $cols as $c ) {
		if ( isset( $_POST[$c] ) ) $data[$c] = sanitize_text_field( $_POST[$c] );
	}

	if ( isset( $data['leads'] ) ) {
		/* re-compute N/A = leads - (connected+not_connected+relevant) */
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT connected_number, not_connected, relevant FROM {$wpdb->prefix}lcm_campaigns WHERE id=%d", $id
		), ARRAY_A );
		if ( $row ) {
			$data['not_available'] = max( 0,
				intval( $data['leads'] ) -
				intval( $row['connected_number'] ) -
				intval( $row['not_connected'] )  -
				intval( $row['relevant'] )
			);
		}
	}

	if ( empty( $data ) ) wp_send_json_success();
	$wpdb->update( $wpdb->prefix.'lcm_campaigns', $data, [ 'id'=>$id ] );
	wp_send_json_success();
}

}
