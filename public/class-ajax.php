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

    $campaign_id = absint($_POST['campaign_id']);
    $date        = sanitize_text_field($_POST['date']);
    $reach       = floatval($_POST['reach']);
    $impressions = floatval($_POST['impressions']);
    $spent       = floatval($_POST['amount_spent']);

    $table = "{$wpdb->prefix}lcm_campaign_daily_tracker";

    // upsert
    $wpdb->replace(
      $table,
      [ 'campaign_id'=>$campaign_id,
        'tracker_date'=>$date,
        'reach'=>$reach,
        'impressions'=>$impressions,
        'amount_spent'=>$spent ]
    );

    wp_send_json_success();
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
    $page        = max(1, (int)($_GET['page']     ?? 1));
    $per_page    = 32;                        // fixed page size
    $offset      = ($page - 1) * $per_page;

    // ② Build WHERE
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
 $filter_camp = absint( $_GET['campaign_id'] ?? 0 );
    if ( $filter_camp ) {
        $where .= $wpdb->prepare( " AND campaign_id = %d", $filter_camp );
    }

    // ③ Lead aggregates by day
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
        ORDER BY lead_date
    ", ARRAY_A );

    // ④ Tracker data by day
    $trackers = $wpdb->get_results( "
        SELECT
            track_date    AS date,
            reach,
            impressions,
            amount_spent
        FROM {$wpdb->prefix}lcm_campaign_daily_tracker
        {$where}      /* same date filter on track_date */
        ORDER BY track_date
    ", ARRAY_A );

    // ⑤ Merge leads + trackers
    $map = [];
    foreach ( $trackers as $t ) {
        $map[ $t['date'] ] = $t;
    }
    $rows = [];
    foreach ( $leads as $l ) {
        $d   = $l['date'];
        $t   = $map[$d] ?? [ 'reach'=>0, 'impressions'=>0, 'amount_spent'=>0 ];
        $conn = (int)$l['relevant'] + (int)$l['not_relevant'];
        $rows[] = array_merge( [
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

    // ⑥ Summary (over all rows)
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

    // ⑦ Pagination slice
    $total_days = count($rows);
    $rows       = array_slice($rows, $offset, $per_page);

    wp_send_json_success([
        'summary'    => $summary,
        'rows'       => $rows,
        'total_days' => $total_days,
    ]);
}

}
 
