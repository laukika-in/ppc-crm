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
    add_action( 'wp_ajax_lcm_get_daily_tracker_rows',   [ $this, 'get_daily_tracker_rows' ] );
    add_action( 'wp_ajax_nopriv_lcm_get_daily_tracker_rows', [ $this, 'forbid' ] );
    add_action( 'wp_ajax_lcm_get_campaign_leads_json', [ $this, 'get_campaign_leads_json' ] );
 
    //add_action('wp_ajax_lcm_get_campaign_detail_rows', [ $this, 'get_campaign_detail_rows' ]);

    add_action( 'wp_ajax_lcm_get_campaign_detail_rows',   [ $this, 'get_campaign_detail_rows' ] );
    add_action( 'wp_ajax_nopriv_lcm_get_campaign_detail_rows', [ $this, 'forbid' ] );

    add_action( 'wp_ajax_lcm_save_daily_tracker',         [ $this, 'save_daily_tracker' ] );
    add_action( 'wp_ajax_nopriv_lcm_save_daily_tracker',  [ $this, 'forbid' ] );

    add_action( 'wp_ajax_lcm_update_campaign_daily_totals', [ $this, 'update_campaign_daily_totals' ] );
    add_action( 'wp_ajax_nopriv_lcm_update_campaign_daily_totals', [ $this, 'forbid' ] );
add_action( 'wp_ajax_lcm_export_csv',    [ $this, 'export_csv' ] );
    add_action( 'wp_ajax_nopriv_lcm_export_csv', [ $this, 'export_csv' ] );
    

 }

private function verify() {
    check_ajax_referer( 'lcm_ajax', 'nonce' );
    if ( ! current_user_can( 'read' ) ) wp_send_json_error( [ 'msg'=>'No permission' ], 403 );
}
public function forbid(){ wp_send_json_error( [ 'msg'=>'Login required' ], 401 ); }

 
public function get_leads() {
        $this->verify();
        $user      = wp_get_current_user();
        $user_id   = $user->ID;
        $is_client = in_array( 'client', (array) $user->roles, true );


            $lead_data = isset( $_REQUEST['lead_data'] ) && is_array( $_REQUEST['lead_data'] )
               ? wp_unslash( (array) $_REQUEST['lead_data'] )
               : [];
        global $wpdb;
     $client_id = $is_client
             ? $user->ID
             : absint( $_REQUEST['client_id'] ?? 0 );

        // start with a no-op WHERE clause
        $where = 'WHERE 1=1';


        if ( $client_id ) {
            $where .= $wpdb->prepare( " AND client_id = %d", $client_id );
        }
       if ( ! empty( $_REQUEST['date_from'] ) ) {
        $where .= $wpdb->prepare( " AND lead_date >= %s", sanitize_text_field($_REQUEST['date_from']) );
        }
        if ( ! empty( $_REQUEST['date_to'] ) ) {
        $where .= $wpdb->prepare( " AND lead_date <= %s", sanitize_text_field($_REQUEST['date_to']) );
        }
            if ( ! empty( $_REQUEST['ad_name'] ) ) {
            $where .= $wpdb->prepare( " AND ad_name = %s", sanitize_text_field($_REQUEST['ad_name']) );
        }
        if ( ! empty( $_REQUEST['adset'] ) ) {
            $where .= $wpdb->prepare( " AND adset = %s", sanitize_text_field($_REQUEST['adset']) );
        }
        if ( ! empty( $_REQUEST['day'] ) ) {
            $where .= $wpdb->prepare( " AND day = %s", sanitize_text_field($_REQUEST['day']) );
        }
        if ( ! empty( $_REQUEST['client_type'] ) ) {
            $where .= $wpdb->prepare( " AND client_type = %s", sanitize_text_field($_REQUEST['client_type']) );
        }
        if ( ! empty( $_REQUEST['source'] ) ) {
            $where .= $wpdb->prepare( " AND source = %s", sanitize_text_field($_REQUEST['source']) );
        }
    if ( ! empty( $_REQUEST['attempt_type'] ) ) {
        $where .= $wpdb->prepare(
            " AND attempt_type = %s",
            sanitize_text_field( $_REQUEST['attempt_type'] )
        );
    }

    // Attempt Status filter
    if ( ! empty( $_REQUEST['attempt_status'] ) ) {
        $where .= $wpdb->prepare(
            " AND attempt_status = %s",
            sanitize_text_field( $_REQUEST['attempt_status'] )
        );
    }
    if ( ! empty( $_REQUEST['store_visit_status'] ) ) {
        $where .= $wpdb->prepare( " AND store_visit_status = %s", sanitize_text_field($_REQUEST['store_visit_status']) );
    }
    if ( ! empty( $_REQUEST['occasion'] ) ) {
        $where .= $wpdb->prepare( " AND occasion = %s", sanitize_text_field($_REQUEST['occasion']) );
    }
    if ( ! empty( $_REQUEST['search'] ) ) {
        $s = '%' . $wpdb->esc_like( $_REQUEST['search'] ) . '%';
        $where .= $wpdb->prepare( " AND ( name LIKE %s OR phone_number LIKE %s OR email LIKE %s )", $s, $s, $s );
    }
    if ( ! empty( $_REQUEST['budget'] ) ) {
        $b = '%' . $wpdb->esc_like( $_REQUEST['budget'] ) . '%';
        $where .= $wpdb->prepare( " AND budget LIKE %s", $b );
    }
    if ( ! empty( $_REQUEST['product_interest'] ) ) {
        $p = '%' . $wpdb->esc_like( $_REQUEST['product_interest'] ) . '%';
        $where .= $wpdb->prepare( " AND product_interest LIKE %s", $p );
    }
    
        $city = sanitize_text_field( $_REQUEST['city'] ?? '' );
        if ( $city ) {
            $where .= $wpdb->prepare(
                " AND location LIKE %s",
                '%' . $wpdb->esc_like( $city ) . '%'
            );
        }
 

        $p  = max(1, (int)($_REQUEST['page']     ?? 1));
        $pp = max(1, (int)($_REQUEST['per_page'] ?? 100));
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
 
        // figure out which dropdown we’re linking by:
        if ( $data['source'] === 'Google' ) {
        // ad_name dropdown now hands us the campaign post_id
        $data['campaign_id'] = absint( $data['ad_name'] );
        } else {
        // adset dropdown now hands us the campaign post_id
        $data['campaign_id'] = absint( $data['adset'] );
        }

        // sanity‐check it
        if ( ! $data['campaign_id'] ) {
        wp_send_json_error([ 'msg'=>'Please pick a valid campaign or adset' ], 400);
        }

		$post_id=wp_insert_post([
			'post_type'=>'lcm_lead','post_status'=>'publish','post_title'=>$data['uid']
		],true);
        if ( isset( $data['campaign_id'], $data['lead_date'] ) ) {
            $this->update_campaign_daily_totals( $data['campaign_id'], $data['lead_date'] );
        }

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
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            $ui = new PPC_CRM_Admin_UI();
            $ui->recount_total_leads( $data['campaign_id'] );
            $ui->recount_campaign_counters( $data['campaign_id'] );
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
        // figure out which dropdown we’re linking by:
        if ( $data['source'] === 'Google' ) {
        // ad_name dropdown now hands us the campaign post_id
        $data['campaign_id'] = absint( $data['ad_name'] );
        } else {
        // adset dropdown now hands us the campaign post_id
        $data['campaign_id'] = absint( $data['adset'] );
        }

        // sanity‐check it
        if ( ! $data['campaign_id'] ) {
        wp_send_json_error([ 'msg'=>'Please pick a valid campaign or adset' ], 400);
        }
        
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
        if ( isset( $data['campaign_id'], $data['lead_date'] ) ) {
            $this->update_campaign_daily_totals( $data['campaign_id'], $data['lead_date'] );
        }

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
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            $ui = new PPC_CRM_Admin_UI();
            $ui->recount_total_leads( $data['campaign_id'] );
            $ui->recount_campaign_counters( $data['campaign_id'] );
            }
            wp_send_json_success();
    }

    /* ---------- 3) Delete saved lead row ------------------------------- */

    public function delete_lead() {
        $this->verify();

        $id = absint( $_POST['id'] ?? 0 );
        if ( ! $id ) {
            wp_send_json_error( [ 'msg' => 'Missing ID' ], 400 );
        }

        global $wpdb;
        // ① grab the campaign_id for this lead
        $campaign_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT campaign_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
            $id
        ) );

        // ② fetch the WP post_id so we can delete it
        $lead = $wpdb->get_row( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}lcm_leads WHERE id = %d",
            $id
        ), ARRAY_A );
        if ( ! $lead || empty( $lead['post_id'] ) ) {
            wp_send_json_error( [ 'msg' => 'Lead not found' ], 404 );
        }

        // ③ remove from custom table & delete the post
        $wpdb->delete( $wpdb->prefix . 'lcm_leads', [ 'id' => $id ] );
        wp_delete_post( (int) $lead['post_id'], true );

        // ④ re‐count now that it’s gone
        if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
            $ui = new PPC_CRM_Admin_UI();
            $ui->recount_total_leads( $campaign_id );
            $ui->recount_campaign_counters( $campaign_id );
        }

        // ⑤ return updated total for pager
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads" );
        wp_send_json_success( [ 'total' => $total ] );
    }
 
public function get_campaigns() {
    $this->verify();

    $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $is_client = in_array( 'client', (array) $user->roles, true );

    global $wpdb;
    $p  = max(1, (int)($_REQUEST['page'] ?? 1));
    $pp = max(1, (int)($_REQUEST['per_page'] ?? 100));
    $o  = ($p - 1) * $pp;

    $table = $wpdb->prefix . 'lcm_campaigns';
    $where = 'WHERE 1=1';

  // Month filter
  if ( ! empty( $_REQUEST['month'] ) ) {
    $where .= $wpdb->prepare( " AND month = %s", sanitize_text_field( $_REQUEST['month'] ) );
  }
    $lead_date = sanitize_text_field($_REQUEST['lead_date'] ?? '');
    if ($lead_date) {
        $where .= $wpdb->prepare(" AND lead_date = %s", $lead_date);
    }
  // Location filter (partial match)
  if ( ! empty( $_REQUEST['location'] ) ) {
    $where .= $wpdb->prepare( " AND location LIKE %s", '%' . $wpdb->esc_like( $_REQUEST['location'] ) . '%' );
  }

  // Store Visit filter
  if ( ! empty( $_REQUEST['store_visit'] ) ) {
    if ( $_REQUEST['store_visit'] === 'yes' ) {
      $where .= " AND store_visit > 0";
    } else {
      $where .= " AND store_visit = 0";
    }
  }
    if ( ! empty( $_REQUEST['date_from'] ) ) {
        $where .= $wpdb->prepare( " AND campaign_date >= %s", sanitize_text_field($_REQUEST['date_from']) );
    }
    if ( ! empty( $_REQUEST['date_to'] ) ) {
        $where .= $wpdb->prepare( " AND campaign_date <= %s", sanitize_text_field($_REQUEST['date_to']) );
    }
  // Connected filter
  if ( ! empty( $_REQUEST['has_connected'] ) ) {
    if ( $_REQUEST['has_connected'] === 'yes' ) {
      $where .= " AND connected_number > 0";
    } else {
      $where .= " AND connected_number = 0";
    }
  }
    // Filter by role
    if ($is_client) {
        $where .= $wpdb->prepare(" AND client_id = %d", $user_id);
    } elseif (!empty($_REQUEST['client_id'])) {
        $where .= $wpdb->prepare(" AND client_id = %d", absint($_REQUEST['client_id']));
    }

    // Total count
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table $where");

    // Campaign rows
    $rows = $wpdb->get_results(
    $wpdb->prepare("SELECT *, not_relevant FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d", $pp, $o),
    ARRAY_A
 );

    // Attach totals from daily tracker
    foreach ($rows as &$row) {
        $post_id = (int) $row['post_id'];
        $totals = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    SUM(reach) as total_reach, 
                    SUM(impressions) as total_impressions, 
                    SUM(amount_spent) as total_spent 
                FROM {$wpdb->prefix}lcm_campaign_daily_tracker 
                WHERE campaign_id = %d",
                $post_id
            ),
            ARRAY_A
        );

        $row['total_reach']       = (int) ($totals['total_reach'] ?? 0);
        $row['total_impressions'] = (int) ($totals['total_impressions'] ?? 0);
        $row['total_spent']       = (float) ($totals['total_spent'] ?? 0);
    }
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
    if ( class_exists( 'PPC_CRM_Admin_UI' ) ) {
        // figure out the WP post ID for this campaign row:
        $campaign_post_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}lcm_campaigns WHERE id = %d",
            $row_id
        ) );

        $ui = new PPC_CRM_Admin_UI();
        // re-count total leads (will also write into 'leads' column)
        $ui->recount_total_leads( $campaign_post_id );
        // re-count all the call/result counters + CPL
        $ui->recount_campaign_counters( $campaign_post_id );
    }
    wp_send_json_success();
}

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

public function get_campaign_leads_json() {
    $this->verify();
    global $wpdb;

    $cid   = absint($_GET['campaign_id'] ?? 0);
    $from  = sanitize_text_field($_GET['from'] ?? '');
    $to    = sanitize_text_field($_GET['to']   ?? '');
    $month = sanitize_text_field($_GET['month'] ?? '');

    if (!$cid) {
        wp_send_json_error('Missing campaign ID', 400);
    }

    // build your WHERE
    $where = $wpdb->prepare("campaign_id = %d", $cid);

    if ($from && $to) {
        $where .= $wpdb->prepare(" AND lead_date BETWEEN %s AND %s", $from, $to);
    } elseif (preg_match('/^(\d{4})-(\d{2})$/',$month,$m)) {
        $year = (int)$m[1];
        $mon  = (int)$m[2];
        $where .= $wpdb->prepare(" AND YEAR(lead_date)=%d AND MONTH(lead_date)=%d", $year, $mon);
    }

    $days = $wpdb->get_results("
        SELECT lead_date AS date, COUNT(*) AS leads
            FROM {$wpdb->prefix}lcm_leads
        WHERE $where
        GROUP BY lead_date
        ORDER BY lead_date ASC", ARRAY_A);

  // 2) summary tallies
  $total_leads = array_sum(wp_list_pluck($days,'leads'));
  $types = $wpdb->get_results( $wpdb->prepare(
    "SELECT attempt_type, COUNT(*) AS qty
       FROM {$wpdb->prefix}lcm_leads
      WHERE campaign_id = %d
      GROUP BY attempt_type",
    $cid
  ), ARRAY_A );
  $status = $wpdb->get_results( $wpdb->prepare(
    "SELECT attempt_status, COUNT(*) AS qty
       FROM {$wpdb->prefix}lcm_leads
      WHERE campaign_id = %d
        AND attempt_status='Store Visit Scheduled'",
    $cid
  ), ARRAY_A );
  $visit = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads
      WHERE campaign_id=%d AND store_visit_status='Show'",
    $cid
  ) );

  wp_send_json_success([
    'days'         => $days,
    'total'        => $total_leads,
    'by_type'      => $types,
    'scheduled'    => (int)$status[0]->qty ?? 0,
    'visit'        => (int)$visit,
  ]);
}

public function get_campaign_detail_rows() {
    $this->verify();
    global $wpdb;

    // ─── Filters from JS ───────────────────────────────────────────
    $campaign_id = absint( $_GET['campaign_id'] ?? 0 );
    $month       = sanitize_text_field( $_GET['month'] ?? '' );
    $from        = sanitize_text_field( $_GET['from']  ?? '' );
    $to          = sanitize_text_field( $_GET['to']    ?? '' );
    $page        = max(1, (int)($_GET['page']     ?? 1));
    $per_page    = 32;                        // fixed page size
    $offset      = ($page - 1) * $per_page;

    // ─── Build WHERE ──────────────────────────────────────────────
    $where = $wpdb->prepare( "WHERE campaign_id = %d", $campaign_id );
    if ( $month ) {
        $where .= $wpdb->prepare(
            " AND DATE_FORMAT(lead_date,'%%Y-%%m') = %s",
            $month
        );
    }
    if ( $from ) {
        $where .= $wpdb->prepare( " AND lead_date >= %s", $from );
    }
    if ( $to ) {
        $where .= $wpdb->prepare( " AND lead_date <= %s", $to );
    }

    // ─── Aggregate leads/day ──────────────────────────────────────
    $leads = $wpdb->get_results( "
        SELECT
        lead_date AS date,
        COUNT(*) AS total_leads,
        SUM(attempt_type='Connected:Relevant') AS relevant,
        SUM(attempt_type='Connected:Not Relevant') AS not_relevant,
        SUM(attempt_type='Not Connected') AS not_connected,
        SUM(attempt_type='N/A') AS not_available,
        SUM(attempt_status='Store Visit Scheduled') AS scheduled_visit,
        SUM(store_visit_status='Show') AS store_visit
        FROM {$wpdb->prefix}lcm_leads
        {$where}
        GROUP BY lead_date
        ORDER BY lead_date
    ", ARRAY_A );

    // ─── Pull trackers/day ────────────────────────────────────────
    $track_where = str_replace('lead_date', 'track_date', $where);
    $trackers = $wpdb->get_results( "
        SELECT
            track_date   AS date,
            reach,
            impressions,
            amount_spent
        FROM {$wpdb->prefix}lcm_campaign_daily_tracker
        {$track_where}
        ORDER BY track_date
    ", ARRAY_A );

    // ─── Merge into $rows[] ───────────────────────────────────────
    $map = [];
    foreach ( $trackers as $t ) {
        $map[ $t['date'] ] = $t;
    }
    $rows = [];
    foreach ( $leads as $l ) {
        $d    = $l['date'];
        $t    = $map[ $d ] ?? [ 'reach'=>0,'impressions'=>0,'amount_spent'=>0 ];
        $conn = (int)$l['relevant'] + (int)$l['not_relevant'];
        $rows[] = array_merge( [
            'date'            => $d,
            'total_leads'     => (int)$l['total_leads'],
            'relevant'        => (int)$l['relevant'],
            'not_relevant'    => (int)$l['not_relevant'],
            'not_connected'   => (int)$l['not_connected'],
            'not_available'   => (int)$l['not_available'],
            'scheduled_visit' => (int)$l['scheduled_visit'],
            'store_visit'     => (int)$l['store_visit'],
            'connected_total' => $conn,
        ], $t );
    }

    // ─── Summary ──────────────────────────────────────────────────
    $summary = array_fill_keys([
    'total_leads','relevant','not_relevant',
    'not_connected','not_available',
    'scheduled_visit','store_visit','connected'
    ], 0 );

    foreach ( $rows as $r ) {
    $summary['total_leads']     += (int) ( $r['total_leads']     ?? 0 );
    $summary['relevant']        += (int) ( $r['relevant']        ?? 0 );
    $summary['not_relevant']    += (int) ( $r['not_relevant']    ?? 0 );
    $summary['not_connected']   += (int) ( $r['not_connected']   ?? 0 );
    $summary['not_available']   += (int) ( $r['not_available']   ?? 0 );
    $summary['scheduled_visit'] += (int) ( $r['scheduled_visit'] ?? 0 );
    $summary['store_visit']     += (int) ( $r['store_visit']     ?? 0 );
    }
    $summary['connected'] = $summary['relevant'] + $summary['not_relevant'];

 
    $total_days = count( $rows );
    $rows       = array_slice( $rows, $offset, $per_page );

    wp_send_json_success([
        'summary'    => $summary,
        'rows'       => $rows,
        'total_days' => $total_days,
    ]);
}
 
public function save_daily_tracker() {
    $this->verify();
    global $wpdb;

    $campaign_id = absint( $_POST['campaign_id'] );
    $date        = sanitize_text_field( $_POST['date'] );
    $reach       = floatval( $_POST['reach'] );
    $impressions = floatval( $_POST['impressions'] );
    $spent       = floatval( $_POST['amount_spent'] );
    $row_id      = isset( $_POST['row_id'] ) ? absint( $_POST['row_id'] ) : 0;

    $table = "{$wpdb->prefix}lcm_campaign_daily_tracker";

    if ( $row_id ) {
        // Update existing row by primary key
        $wpdb->update(
            $table,
            [
                'reach'        => $reach,
                'impressions'  => $impressions,
                'amount_spent' => $spent,
            ],
            [ 'id' => $row_id ]
        );
    } else {
        // Insert new row (upsert by date)
        $wpdb->replace(
            $table,
            [
                'campaign_id'  => $campaign_id,
                'track_date'   => $date,        // <-- use the actual column name
                'reach'        => $reach,
                'impressions'  => $impressions,
                'amount_spent' => $spent,
            ]
        );
        $row_id = $wpdb->insert_id;
    }

    wp_send_json_success( [ 'row_id' => $row_id ] );
}

public function update_campaign_daily_totals() {
    $this->verify();
    // you can call your existing recalc logic here
    wp_send_json_success();
}

public function get_daily_tracker_rows() {
    $this->verify();
    global $wpdb;

    // ① Read filters + pagination
    $campaign_id = absint( $_GET['campaign_id'] ?? 0 );
    $month       = sanitize_text_field( $_GET['month'] ?? '' );
    $from        = sanitize_text_field( $_GET['from']  ?? '' );
    $to          = sanitize_text_field( $_GET['to']    ?? '' );
    $page        = max(1, (int)($_GET['page'] ?? 1));
    $per_page    = 31;
    $offset      = ($page - 1) * $per_page;
if (empty($month) && empty($from) && empty($to)) {
    $month = date('Y-m'); // e.g. "2025-07"
}
    // ② Build WHERE clause for leads (uses lead_date)
    $where = "WHERE 1=1";
    if ( $month ) {
        $where .= $wpdb->prepare(
            " AND DATE_FORMAT(lead_date,'%%Y-%%m') = %s",
            $month
        );
    }
    if ( $from ) {
        $where .= $wpdb->prepare( " AND lead_date >= %s", $from );
    }
    if ( $to ) {
        $where .= $wpdb->prepare( " AND lead_date <= %s", $to );
    }
    if ( $campaign_id ) {
        $where .= $wpdb->prepare( " AND campaign_id = %d", $campaign_id );
    }

    // ③ Build WHERE clause for tracker (uses track_date)
    $tracker_where = "WHERE 1=1";
    if ( $month ) {
        $tracker_where .= $wpdb->prepare(
            " AND DATE_FORMAT(track_date,'%%Y-%%m') = %s",
            $month
        );
    }
    if ( $from ) {
        $tracker_where .= $wpdb->prepare( " AND track_date >= %s", $from );
    }
    if ( $to ) {
        $tracker_where .= $wpdb->prepare( " AND track_date <= %s", $to );
    }
    if ( $campaign_id ) {
        $tracker_where .= $wpdb->prepare( " AND campaign_id = %d", $campaign_id );
    }

    // ④ Lead aggregates by day
    $leads = $wpdb->get_results( "
        SELECT
            lead_date        AS date,
            COUNT(*)         AS total_leads,
            SUM(attempt_type='Connected:Relevant')     AS relevant,
            SUM(attempt_type='Connected:Not Relevant') AS not_relevant,
            SUM(attempt_type='Not Connected')          AS not_connected,
            SUM(attempt_type='N/A')                    AS not_available,
            SUM(attempt_status='Store Visit Scheduled') AS scheduled_visit,
            SUM(store_visit_status='Show')             AS store_visit
        FROM {$wpdb->prefix}lcm_leads
        {$where}
        GROUP BY lead_date
        ORDER BY lead_date DESC
    ", ARRAY_A );

    // ⑤ Tracker data by day
    $trackers = $wpdb->get_results( "
        SELECT
            track_date    AS date,
            reach,
            impressions,
            amount_spent
        FROM {$wpdb->prefix}lcm_campaign_daily_tracker
        {$tracker_where}
        ORDER BY track_date DESC
    ", ARRAY_A );

    // ⑥ Merge leads + trackers
    $map = [];
    foreach ( $trackers as $t ) {
        $map[ $t['date'] ] = $t;
    }
    $rows = [];
    foreach ( $leads as $l ) {
        $d = $l['date'];
        $t = $map[$d] ?? [ 'reach'=>0, 'impressions'=>0, 'amount_spent'=>0 ];
        $conn = (int)$l['relevant'] + (int)$l['not_relevant'];
        $rows[] = array_merge([
            'date'           => $d,
            'total_leads'    => (int)$l['total_leads'],
            'relevant'       => (int)$l['relevant'],
            'not_relevant'   => (int)$l['not_relevant'],
            'not_connected'  => (int)$l['not_connected'],
            'not_available'  => (int)$l['not_available'],
            'scheduled_visit'=> (int)$l['scheduled_visit'],
            'store_visit'    => (int)$l['store_visit'],
            'connected_total'=> $conn,
        ], $t );
    }

    // ⑦ Summary
    $summary = [
        'total_leads'     => 0,
        'relevant'        => 0,
        'not_relevant'    => 0,
        'not_connected'   => 0,
        'not_available'   => 0,
        'scheduled_visit' => 0,
        'store_visit'     => 0,
        'connected'       => 0,
    ];
    foreach ( $rows as $r ) {
        $summary['total_leads']     += $r['total_leads'];
        $summary['relevant']        += $r['relevant'];
        $summary['not_relevant']    += $r['not_relevant'];
        $summary['not_connected']   += $r['not_connected'];
        $summary['not_available']   += $r['not_available'];
        $summary['scheduled_visit'] += $r['scheduled_visit'];
        $summary['store_visit']     += $r['store_visit'];
    }
    $summary['connected'] = $summary['relevant'] + $summary['not_relevant'];

    // ⑧ Pagination slice
    $total_days = count($rows);
    $rows       = array_slice($rows, $offset, $per_page);

    wp_send_json_success([
        'summary'    => $summary,
        'rows'       => $rows,
        'total_days' => $total_days,
    ]);
}

public function export_csv() {
    $this->verify();
    global $wpdb;

    $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';

    $leads_table     = $wpdb->prefix . 'lcm_leads';
    $campaigns_table = $wpdb->prefix . 'lcm_campaigns';
    $users_table     = $wpdb->users;
    $tracker_table   = $wpdb->prefix . 'lcm_campaign_daily_tracker';

    header('Content-Type: text/csv; charset=utf-8');
    
    switch ($type) {
        case 'leads':
            header('Content-Disposition: attachment; filename="lcm-leads.csv"');
        $where = [];

        if (current_user_can('client')) {
            $user_id = get_current_user_id();
            $where[] = "l.client_id = {$user_id}";
        } elseif (!empty($_GET['client_id'])) {
            $client_id = absint($_GET['client_id']);
            $where[] = "l.client_id = {$client_id}";
        }

        if (!empty($_GET['from']) && !empty($_GET['to'])) {
            $from = esc_sql($_GET['from']);
            $to   = esc_sql($_GET['to']);
            $where[] = "l.lead_date BETWEEN '{$from}' AND '{$to}'";
        }

        if (!empty($_GET['source'])) {
            $where[] = $wpdb->prepare("l.source = %s", $_GET['source']);
        }
        if (!empty($_GET['client_type'])) {
            $where[] = $wpdb->prepare("l.client_type = %s", $_GET['client_type']);
        }
        if (!empty($_GET['ad_name'])) {
            $where[] = $wpdb->prepare("l.ad_name = %d", absint($_GET['ad_name']));
        }
        if (!empty($_GET['adset'])) {
            $where[] = $wpdb->prepare("l.adset = %d", absint($_GET['adset']));
        }
        if (!empty($_GET['day'])) {
            $where[] = $wpdb->prepare("l.day = %s", $_GET['day']);
        }
        if (!empty($_GET['attempt_type'])) {
            $where[] = $wpdb->prepare("l.attempt_type = %s", $_GET['attempt_type']);
        }
        if (!empty($_GET['attempt_status'])) {
            $where[] = $wpdb->prepare("l.attempt_status = %s", $_GET['attempt_status']);
        }
        if (!empty($_GET['store_visit_status'])) {
            $where[] = $wpdb->prepare("l.store_visit_status = %s", $_GET['store_visit_status']);
        }
        if (!empty($_GET['occasion'])) {
            $where[] = $wpdb->prepare("l.occasion = %s", $_GET['occasion']);
        }
        if (!empty($_GET['city'])) {
            $where[] = $wpdb->prepare("l.city LIKE %s", '%' . $wpdb->esc_like($_GET['city']) . '%');
        }
        if (!empty($_GET['budget'])) {
            $where[] = $wpdb->prepare("l.budget LIKE %s", '%' . $wpdb->esc_like($_GET['budget']) . '%');
        }
        if (!empty($_GET['product'])) {
            $where[] = $wpdb->prepare("l.product_interest LIKE %s", '%' . $wpdb->esc_like($_GET['product']) . '%');
        }
        if (!empty($_GET['text'])) {
            $text = '%' . $wpdb->esc_like($_GET['text']) . '%';
            $where[] = "(l.lead_name LIKE '{$text}' OR l.phone LIKE '{$text}' OR l.email LIKE '{$text}')";
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                    SELECT
                    l.*,
                    u.display_name      AS client_label,
                    c1.campaign_name    AS ad_name_label,
                    c2.adset            AS adset_label
                    FROM {$leads_table} l
                    LEFT JOIN {$users_table}        u  ON u.ID          = l.client_id
                    LEFT JOIN {$campaigns_table}    c1 ON c1.post_id    = l.ad_name
                    LEFT JOIN {$campaigns_table}    c2 ON c2.post_id    = l.adset
                    {$where_clause}
                ";

            $rows = $wpdb->get_results($sql, ARRAY_A);

            foreach ($rows as &$row) {
                $row['client_id'] = $row['client_label'] ?? '';
                // campaign_id remains as-is
                $row['ad_name']   = $row['ad_name_label'] ?? '';
                $row['adset']     = $row['adset_label'] ?? '';
                unset($row['client_label'], $row['ad_name_label'], $row['adset_label']);
            }
            unset($row);
            break;

        case 'campaigns':
            header('Content-Disposition: attachment; filename="lcm-campaigns.csv"');
        $where = [];

        if (current_user_can('client')) {
            $user_id = get_current_user_id();
            $where[] = "c.client_id = {$user_id}";
        } elseif (!empty($_GET['client_id'])) {
            $client_id = absint($_GET['client_id']);
            $where[] = "c.client_id = {$client_id}";
        }

        if (!empty($_GET['from']) && !empty($_GET['to'])) {
            $from = esc_sql($_GET['from']);
            $to   = esc_sql($_GET['to']);
            $where[] = "c.campaign_date BETWEEN '{$from}' AND '{$to}'";

        }

        if (!empty($_GET['month'])) {
            $month = esc_sql($_GET['month']);
            $where[] = $wpdb->prepare("c.month = %s", $month);
        }

        if (!empty($_GET['location'])) {
            $where[] = $wpdb->prepare("c.location LIKE %s", '%' . $wpdb->esc_like($_GET['location']) . '%');
        }

        $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

                    $sql = "
            SELECT
            c.*,
            u.display_name AS client_label
            FROM {$campaigns_table} c
            LEFT JOIN {$users_table} u ON u.ID = c.client_id
            {$where_clause}
        ";

            $rows = $wpdb->get_results($sql, ARRAY_A);

            foreach ($rows as &$row) {
                $row['client_id'] = $row['client_label'] ?? '';
                unset($row['client_label']);
            }
            unset($row);
            break;

       case 'daily_tracker':
    header('Content-Disposition: attachment; filename="lcm-daily-tracker.csv"');

    $campaign_id = absint($_GET['campaign_id'] ?? 0);
    $month       = sanitize_text_field($_GET['month'] ?? '');
    $from        = sanitize_text_field($_GET['from'] ?? '');
    $to          = sanitize_text_field($_GET['to'] ?? '');

    $where = "WHERE 1=1";

    if ($campaign_id) {
        $where .= $wpdb->prepare(" AND t.campaign_id = %d", $campaign_id);
    }
    if ($month) {
        $where .= $wpdb->prepare(" AND DATE_FORMAT(t.track_date, '%%Y-%%m') = %s", $month);
    }
    if ($from) {
        $where .= $wpdb->prepare(" AND t.track_date >= %s", $from);
    }
    if ($to) {
        $where .= $wpdb->prepare(" AND t.track_date <= %s", $to);
    }

    // Client role restriction
    if (current_user_can('client')) {
        $client_id = get_current_user_id();
        $where .= $wpdb->prepare(" AND c.client_id = %d", $client_id);
    }

    $sql = "
        SELECT
            t.*,
            c.campaign_title AS campaign_label
        FROM {$tracker_table} t
        LEFT JOIN {$campaigns_table} c ON c.post_id = t.campaign_id
        {$where}
        ORDER BY t.track_date DESC
    ";

    $rows = $wpdb->get_results($sql, ARRAY_A);

    foreach ($rows as &$row) {
        $row['campaign_id'] = $row['campaign_label'] ?? $row['campaign_id'];
        unset($row['campaign_label']);
    }
    unset($row);
    break;


        case 'campaign_detail':
            header('Content-Disposition: attachment; filename="lcm-campaign-detail.csv"');

            $where = [];

            if (!empty($_GET['campaign_id'])) {
                $cid = absint($_GET['campaign_id']);
                $where[] = "t.campaign_id = {$cid}";
            }

            if (!empty($_GET['month'])) {
                $month = esc_sql($_GET['month']);
                $where[] = "DATE_FORMAT(t.track_date, '%Y-%m') = '{$month}'";
            } elseif (!empty($_GET['from']) && !empty($_GET['to'])) {
                $from = esc_sql($_GET['from']);
                $to   = esc_sql($_GET['to']);
                $where[] = "t.track_date BETWEEN '{$from}' AND '{$to}'";
            }

            $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT
                t.*,
                c.campaign_title AS campaign_label
                FROM {$tracker_table} t
                LEFT JOIN {$campaigns_table} c ON c.post_id = t.campaign_id
                {$where_clause}
            ";

            $rows = $wpdb->get_results($sql, ARRAY_A);

            foreach ($rows as &$row) {
                $row['campaign_id'] = $row['campaign_label'] ?? $row['campaign_id'];
                unset($row['campaign_label']);
            }
            unset($row);
            break;


        default:
            wp_send_json_error('Invalid export type');
    }

    if (empty($rows)) {
        wp_send_json_error('No records found');
    }
    error_log('Filters received: ' . json_encode($_GET));
    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys($rows[0]));
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}



}
 
