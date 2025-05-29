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
 $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $is_client = in_array( 'client', (array) $user->roles, true );

    global $wpdb;
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

    global $wpdb;
		$fields = [
			'client_id','lead_title','ad_name','adset','uid','lead_date','lead_time','day',
  'name','phone_number','alt_number','email','location',
  'client_type','source','source_campaign','targeting','budget',
  'product_interest','occasion',
  'attempt','attempt_type','attempt_status','store_visit_status','remarks'
		];
		$data=[];
		foreach($fields as $f){ $data[$f]=sanitize_text_field($_POST[$f]??''); }
 if ( $data['source'] === 'Google' ) {
    $camp_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id
           FROM {$wpdb->prefix}lcm_campaigns
          WHERE campaign_title = %s
          LIMIT 1",
        $data['ad_name']
    ) );
} else {
    $camp_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id
           FROM {$wpdb->prefix}lcm_campaigns
          WHERE adset = %s
          LIMIT 1",
        $data['adset']
    ) );
}
if ( ! $camp_id ) {
    wp_send_json_error([ 'msg' => 'Campaign not found' ], 404);
}
$data['campaign_id'] = (int) $camp_id;

		$post_id=wp_insert_post([
			'post_type'=>'lcm_lead','post_status'=>'publish','post_title'=>$data['uid']
		],true);
		if(is_wp_error($post_id)) wp_send_json_error(['msg'=>$post_id->get_error_message()],500);
		$data['post_id']=$post_id;
		
	$user      = wp_get_current_user();
		$user_id   = $user->ID;
		$is_client = in_array( 'client', (array) $user->roles, true );
	if ( $is_client ) {
		$data['client_id'] = $user->ID;          // force client id
	} else {
		$data['client_id'] = absint( $_POST['client_id'] ?? 0 );
	}

		$wpdb->insert( $wpdb->prefix.'lcm_leads', $data );
		if ( class_exists('PPC_CRM_Admin_UI') ) {
		$ui = new PPC_CRM_Admin_UI(); 
		 $ui->recount_campaign_counters( $data['campaign_id'] );
		$ui->recount_total_leads( $data['ad_name'], $data['adset'] );
		}
		wp_send_json_success();
	}

public function update_lead() {
    $this->verify();

		global $wpdb;
    $id = absint( $_POST['id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'msg'=>'Missing lead ID' ], 400 );
 
    $fields = [
         'client_id','lead_title','ad_name','adset','uid','lead_date','lead_time','day',
        'name','phone_number','alt_number','email','location',
        'client_type','source','source_campaign','targeting','budget',
        'product_interest','occasion',
        'attempt','attempt_type','attempt_status','store_visit_status','remarks'
    ];
    $data = [];
    foreach ( $fields as $f ) {
        $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
    }
if ( $data['source'] === 'Google' ) {
    $camp_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id
           FROM {$wpdb->prefix}lcm_campaigns
          WHERE campaign_title = %s
          LIMIT 1",
        $data['ad_name']
    ) );
} else {
    $camp_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id
           FROM {$wpdb->prefix}lcm_campaigns
          WHERE adset = %s
          LIMIT 1",
        $data['adset']
    ) );
}
if ( ! $camp_id ) {
    wp_send_json_error([ 'msg' => 'Campaign not found' ], 404);
}
$data['campaign_id'] = (int) $camp_id;
    // If client role, force client_id
    $user      = wp_get_current_user();
    $is_client = in_array( 'client', (array) $user->roles, true );
    if ( $is_client ) {
        $data['client_id'] = $user->ID;
    } else {
        $data['client_id'] = absint( $data['client_id'] );
    }

    // Update the custom table row by its own id
    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'lcm_leads',
        $data,
        [ 'id' => $id ],
        array_fill( 0, count( $data ), '%s' ),
        [ '%d' ]
    );

    // Also update the WP post title (UID)
    $lead = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->prefix}lcm_leads WHERE id=%d",
        $id
    ) );
    if ( $lead ) {
        wp_update_post([
            'ID'         => $lead,
            'post_title' => sanitize_text_field( $_POST['uid'] ?? '' ),
        ]);
    }
	if ( class_exists('PPC_CRM_Admin_UI') ) {
		$ui = new PPC_CRM_Admin_UI(); 
		 $ui->recount_campaign_counters( $data['campaign_id'] );
		$ui->recount_total_leads( $data['ad_name'], $data['adset'] );
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
		 "SELECT adset, ad_name, post_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
		$id
	), ARRAY_A );

	if ( ! $lead ) wp_send_json_error( [ 'msg' => 'Lead not found' ], 404 );

	/* Remove from custom table and wp_posts */
	$wpdb->delete( $wpdb->prefix . 'lcm_leads', [ 'id' => $id ] );
	wp_delete_post( $lead['post_id'], true );

     if ( class_exists('PPC_CRM_Admin_UI') ) {
        $ui = new PPC_CRM_Admin_UI();
        $ui->recount_campaign_counters( $data['campaign_id'] );
        $ui->recount_total_leads( $lead['ad_name'], $lead['adset'] );
    }

    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
    wp_send_json_success( [ 'total' => $total ] );   // ← NEW
 
}
/* ---------- Campaign: fetch --------------------------------------- */
public function get_campaigns() {
    $this->verify();

 $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $is_client = in_array( 'client', (array) $user->roles, true );

    global $wpdb;
    $p  = max(1, (int)($_GET['page'] ?? 1));
    $pp = max(1, (int)($_GET['per_page'] ?? 10));
    $o  = ($p - 1) * $pp;

    $table = $wpdb->prefix . 'lcm_campaigns';
    $where = 'WHERE 1=1';

  // Month filter
  if ( ! empty( $_GET['month'] ) ) {
    $where .= $wpdb->prepare( " AND month = %s", sanitize_text_field( $_GET['month'] ) );
  }

  // Location filter (partial match)
  if ( ! empty( $_GET['location'] ) ) {
    $where .= $wpdb->prepare( " AND location LIKE %s", '%' . $wpdb->esc_like( $_GET['location'] ) . '%' );
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
        $wpdb->prepare("SELECT *,not_relevant FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d", $pp, $o),
        ARRAY_A
    );

    wp_send_json(['total' => $total, 'rows' => $rows]);
}

public function create_campaign() {
    $this->verify();
    $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $is_client = in_array( 'client', (array) $user->roles, true );
    // Gather & sanitize all fields, including campaign_date
    $fields = [
        'client_id','campaign_title','campaign_name','month','week','campaign_date','location','adset',
        'leads','reach','impressions','cost_per_lead','amount_spent','cpm',
        'connected_number','not_connected','relevant','not_available',
        'scheduled_store_visit','store_visit'
    ];
    $data = [];
    foreach ( $fields as $f ) {
        $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
    }

    // Force title = adset
    if ( empty( $data['adset'] ) ) {
        wp_send_json_error([ 'msg'=>'Adset required' ], 400);
    }

    // ① Force or validate client_id
    if ( $is_client ) {
        $data['client_id'] = $user->ID;
    } else {
        $data['client_id'] = absint( $data['client_id'] );
        if ( ! $data['client_id'] ) {
            wp_send_json_error([ 'msg'=>'Client is required' ], 400);
        }
    }

    // ② Insert WP post for linking
    $post_id = wp_insert_post([
        'post_type'   => 'lcm_campaign',
        'post_status' => 'publish',
        'post_title'  => $data['campaign_name'],
    ], true);

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error([ 'msg' => $post_id->get_error_message() ], 500);
    }
    $data['post_id'] = $post_id;

    // ③ Write into custom table, including campaign_date
    global $wpdb;
    $wpdb->replace( $wpdb->prefix . 'lcm_campaigns', $data );

    wp_send_json_success();
}

public function update_campaign() {
    $this->verify();

    // 1) The incoming ID is your custom‐table row ID
    $row_id = absint( $_POST['id'] ?? 0 );
    if ( ! $row_id ) {
        wp_send_json_error( [ 'msg' => 'Missing campaign ID' ], 400 );
    }

    // 2) Gather and sanitize exactly the same fields as create_campaign()
    $fields = [
        'client_id','campaign_title','campaign_name','month','week','campaign_date','location','adset',
        'leads','reach','impressions','cost_per_lead','amount_spent','cpm',
        'connected_number','not_connected','relevant','not_available',
        'scheduled_store_visit','store_visit'
    ];
    $data = [];
    foreach ( $fields as $f ) {
        $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
    }

    // 3) Enforce client_id for client‐role users
    $user      = wp_get_current_user();
    $is_client = in_array( 'client', (array) $user->roles, true );
    if ( $is_client ) {
        $data['client_id'] = $user->ID;
    } else {
        $data['client_id'] = absint( $data['client_id'] );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'lcm_campaigns';

    // 4) Update the custom table row by its primary key (id)
    $wpdb->update(
        $table,
        $data,
        [ 'id' => $row_id ],
        // Formats: all TEXT except client_id (= %d)
        array_merge( array_fill(0, count($data), '%s') ),
        [ '%d' ]
    );

    // 5) Fetch the linked WP post ID so we can update its title
    $post_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$table} WHERE id = %d",
        $row_id
    ) );
    if ( $post_id ) {
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => sanitize_text_field( $_POST['campaign_name'] ?? '' ),
        ]);
    }

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
 
}
