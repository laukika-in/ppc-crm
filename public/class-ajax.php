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
    add_action('wp_ajax_lcm_save_daily_tracker', [ $this, 'save_daily_tracker' ]);
    add_action('wp_ajax_lcm_get_daily_tracker_rows', [$this, 'get_daily_tracker_rows']);
    add_action( 'wp_ajax_lcm_get_campaign_leads_json', [ $this, 'get_campaign_leads_json' ] );
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
     $client_id = $is_client
             ? $user->ID
             : absint( $_GET['client_id'] ?? 0 );

// start with a no-op WHERE clause
$where = 'WHERE 1=1';


if ( $client_id ) {
    $where .= $wpdb->prepare( " AND client_id = %d", $client_id );
}
        if ( ! empty( $_GET['date_from'] ) ) {
    $where .= $wpdb->prepare( " AND lead_date >= %s", sanitize_text_field($_GET['date_from']) );
}
if ( ! empty( $_GET['date_to'] ) ) {
    $where .= $wpdb->prepare( " AND lead_date <= %s", sanitize_text_field($_GET['date_to']) );
}
if ( ! empty( $_GET['ad_name'] ) ) {
    $where .= $wpdb->prepare( " AND ad_name = %s", sanitize_text_field($_GET['ad_name']) );
}
if ( ! empty( $_GET['adset'] ) ) {
    $where .= $wpdb->prepare( " AND adset = %s", sanitize_text_field($_GET['adset']) );
}
if ( ! empty( $_GET['day'] ) ) {
    $where .= $wpdb->prepare( " AND day = %s", sanitize_text_field($_GET['day']) );
}
if ( ! empty( $_GET['client_type'] ) ) {
    $where .= $wpdb->prepare( " AND client_type = %s", sanitize_text_field($_GET['client_type']) );
}
if ( ! empty( $_GET['source'] ) ) {
    $where .= $wpdb->prepare( " AND source = %s", sanitize_text_field($_GET['source']) );
}
    if ( ! empty( $_GET['attempt_type'] ) ) {
        $where .= $wpdb->prepare(
            " AND attempt_type = %s",
            sanitize_text_field( $_GET['attempt_type'] )
        );
    }

    // Attempt Status filter
    if ( ! empty( $_GET['attempt_status'] ) ) {
        $where .= $wpdb->prepare(
            " AND attempt_status = %s",
            sanitize_text_field( $_GET['attempt_status'] )
        );
    }
    if ( ! empty( $_GET['store_visit_status'] ) ) {
        $where .= $wpdb->prepare( " AND store_visit_status = %s", sanitize_text_field($_GET['store_visit_status']) );
    }
    if ( ! empty( $_GET['occasion'] ) ) {
        $where .= $wpdb->prepare( " AND occasion = %s", sanitize_text_field($_GET['occasion']) );
    }
    if ( ! empty( $_GET['search'] ) ) {
        $s = '%' . $wpdb->esc_like( $_GET['search'] ) . '%';
        $where .= $wpdb->prepare( " AND ( name LIKE %s OR phone_number LIKE %s OR email LIKE %s )", $s, $s, $s );
    }
    if ( ! empty( $_GET['budget'] ) ) {
        $b = '%' . $wpdb->esc_like( $_GET['budget'] ) . '%';
        $where .= $wpdb->prepare( " AND budget LIKE %s", $b );
    }
    if ( ! empty( $_GET['product_interest'] ) ) {
        $p = '%' . $wpdb->esc_like( $_GET['product_interest'] ) . '%';
        $where .= $wpdb->prepare( " AND product_interest LIKE %s", $p );
    }
    error_log( 'LCM GET params: ' . print_r( $_GET, true ) );
    error_log( 'LCM WHERE clause: ' . $where );

        $p  = max(1,(int)($_GET['page']??1));
        $pp = max(1,(int)($_GET['per_page']??100));
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

/* ---------- Campaign: fetch --------------------------------------- */
public function get_campaigns() {
    $this->verify();

 $user      = wp_get_current_user();
    $user_id   = $user->ID;
    $is_client = in_array( 'client', (array) $user->roles, true );

    global $wpdb;
    $p  = max(1, (int)($_GET['page'] ?? 1));
    $pp = max(1, (int)($_GET['per_page'] ?? 100));
    $o  = ($p - 1) * $pp;

    $table = $wpdb->prefix . 'lcm_campaigns';
    $where = 'WHERE 1=1';

  // Month filter
  if ( ! empty( $_GET['month'] ) ) {
    $where .= $wpdb->prepare( " AND month = %s", sanitize_text_field( $_GET['month'] ) );
  }
    $lead_date = sanitize_text_field($_GET['lead_date'] ?? '');
    if ($lead_date) {
        $where .= $wpdb->prepare(" AND lead_date = %s", $lead_date);
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

 public function save_daily_tracker() {
  check_ajax_referer('lcm_ajax', 'nonce');
  global $wpdb;

  $row_id = absint($_POST['row_id'] ?? 0);
  $campaign_post_id = absint($_POST['campaign_id'] ?? 0);
  $log_date = sanitize_text_field($_POST['date'] ?? '');
  $reach = absint($_POST['reach'] ?? 0);
  $impressions = absint($_POST['impressions'] ?? 0);
  $spent = floatval($_POST['amount_spent'] ?? 0);

  if ($row_id) {
    // Update existing row
    $updated = $wpdb->update(
      $wpdb->prefix . 'lcm_campaign_daily_tracker',
      [
        'reach' => $reach,
        'impressions' => $impressions,
        'amount_spent' => $spent
      ],
      [ 'id' => $row_id ]
    );

    if (false === $updated) {
      wp_send_json_error("Update failed");
    }

    wp_send_json_success("Updated");
  } elseif ($campaign_post_id && $log_date) {
    // Insert new row
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'lcm_campaign_daily_tracker',
        [
            'campaign_id'   => $campaign_post_id,
            'track_date'    => $log_date,
            'reach'         => $reach,
            'impressions'   => $impressions,
            'amount_spent'  => $spent
        ]
    );

    if (!$inserted) {
      wp_send_json_error("Insert failed");
    }

    wp_send_json_success("Inserted");
  }

  wp_send_json_error("Invalid data");
}
public function get_daily_tracker_rows() {
  check_ajax_referer('lcm_ajax', 'nonce');
  global $wpdb;

  $campaign_id = absint($_GET['campaign_id'] ?? 0);
  if (!$campaign_id) {
    wp_send_json_error('Invalid campaign ID');
  }

  $rows = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT id, track_date, reach, impressions, amount_spent
       FROM {$wpdb->prefix}lcm_campaign_daily_tracker
       WHERE campaign_id = %d",
      $campaign_id
    )
  );

  $result = [];
  foreach ($rows as $row) {
    $result[$row->track_date] = [
      'id' => $row->id,
      'reach' => $row->reach,
      'impressions' => $row->impressions,
      'amount_spent' => $row->amount_spent,
    ];
  }

  wp_send_json_success($result);
}

public function get_campaign_leads_json() {
  $this->verify();
  $cid = absint( $_GET['campaign_id'] ?? 0 );
  if ( ! $cid ) wp_send_json_error('Missing campaign ID', 400);

  global $wpdb;
  // 1) per-day lead counts
  $days = $wpdb->get_results( $wpdb->prepare(
    "SELECT lead_date AS date, COUNT(*) AS leads
       FROM {$wpdb->prefix}lcm_leads
      WHERE campaign_id = %d
      GROUP BY lead_date
      ORDER BY lead_date ASC",
    $cid
  ), ARRAY_A );

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


}
 
