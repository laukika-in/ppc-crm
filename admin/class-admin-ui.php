<?php
// admin/class-admin-ui.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Admin_UI {

	/* ---------------------------------------------------------------------
	 * Field definitions (label, type, options)
	 * ------------------------------------------------------------------ */
	private $campaign_fields = [];
	private $lead_fields     = [];

	public function __construct() {

		/* Campaign fields ------------------------------------------------- */
		$this->campaign_fields = [
			'client_id'             => [ 'label' => 'Client',             'type' => 'user-dropdown' ],
            'campaign_title'        => [ 'label' => 'Campaign Title',     'type' => 'text' ],
            'campaign_name'         => [ 'label' => 'Campaign Name',      'type' => 'text' ],
            'month'                 => [ 'label' => 'Month',              'type' => 'select', 'options' => [
                'January','February','March','April','May','June','July','August','September','October','November','December'
            ] ],
            'week'                  => [ 'label' => 'Week',               'type' => 'number', 'step' => '0.1' ],
            'campaign_date'         => [ 'label' => 'Date',               'type' => 'date' ],
            'location'              => [ 'label' => 'Location',           'type' => 'text' ],
            'adset'                 => [ 'label' => 'Adset',              'type' => 'text' ],
            'leads'                 => [ 'label' => 'Leads',              'type' => 'number' ],
            'reach'                 => [ 'label' => 'Reach',              'type' => 'number' ],
            'impressions'           => [ 'label' => 'Impressions',        'type' => 'number' ],
            'cost_per_lead'         => [ 'label' => 'Cost Per Lead',      'type' => 'number', 'step' => 'any' ],
            'amount_spent'          => [ 'label' => 'Amount Spent',       'type' => 'number', 'step' => 'any' ],
            'cpm'                   => [ 'label' => 'CPM',                'type' => 'number', 'step' => 'any' ],
            'connected_number'      => [ 'label' => 'Connected Number',   'type' => 'number' ],
            'not_relevant'          => [ 'label' => 'Not Relevant',       'type' => 'number' ],
            'not_connected'         => [ 'label' => 'Not Connected',      'type' => 'number' ],
            'relevant'              => [ 'label' => 'Relevant',           'type' => 'number' ],
            'not_available'         => [ 'label' => 'N/A',                'type' => 'number' ],
            'scheduled_store_visit' => [ 'label' => 'Sched. Store Visit','type' => 'number' ],
            'store_visit'           => [ 'label' => 'Store Visit',        'type' => 'number' ],
		];

		/* Lead fields ----------------------------------------------------- */
		$this->lead_fields = [
		'client_id'                 => [ 'label' => 'Client',               'type' => 'user-dropdown' ],
            'lead_title'                => [ 'label' => 'Lead Title',           'type' => 'text' ],
            'ad_name'                   => [ 'label' => 'Campaign Name',        'type' => 'select', 'options' => [] ],
            'adset'                     => [ 'label' => 'Adset',                'type' => 'select', 'options' => [] ],
            'uid'                       => [ 'label' => 'UID',                  'type' => 'text' ],
            'lead_date'                 => [ 'label' => 'Date of Lead',         'type' => 'date' ],
            'lead_time'                 => [ 'label' => 'Time of Lead',         'type' => 'time' ],
            'day'                       => [ 'label' => 'Day',                  'type' => 'select', 'options' => [
                'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'
            ] ],
            'name'                      => [ 'label' => 'Name',                 'type' => 'text' ],
            'phone_number'              => [ 'label' => 'Phone Number',         'type' => 'text' ],
            'alt_number'                => [ 'label' => 'Alternative Number',    'type' => 'text' ],
            'email'                     => [ 'label' => 'Email',                'type' => 'email' ],
            'location'                  => [ 'label' => 'Location',             'type' => 'text' ],
            'client_type'               => [ 'label' => 'Client Type',          'type' => 'select', 'options' => [
                'New Client','Existing Client'
            ] ],
            'sources'                   => [ 'label' => 'Sources',              'type' => 'select', 'options' => [
                'Google','Meta','WhatsApp','LinkedIn','Twitter','TikTok','Email','Referral','Organic','Other'
            ] ],
            'source_of_campaign'        => [ 'label' => 'Source of Campaign',    'type' => 'text' ],
            'targeting_of_campaign'     => [ 'label' => 'Targeting of Campaign','type' => 'text' ],
            'budget'                    => [ 'label' => 'Budget',               'type' => 'text' ],
            'product_looking_to_buy'    => [ 'label' => 'Product Looking To Buy','type' => 'text' ],
            'occasion'                  => [ 'label' => 'Occasion',             'type' => 'select', 'options' => [
                'Anniversary','Birthday','Casual Occasion','Engagement/Wedding','Gifting','N/A'
            ] ],
            'for_whom'                  => [ 'label' => 'For Whom',             'type' => 'text' ],
            'final_type'                => [ 'label' => 'Final Type',           'type' => 'text' ],
            'final_sub_type'            => [ 'label' => 'Final Sub Type',       'type' => 'text' ],
            'main_city'                 => [ 'label' => 'Main City',            'type' => 'text' ],
            'store_location'            => [ 'label' => 'Store Location',       'type' => 'text' ],
            'store_visit'               => [ 'label' => 'Store Visit',          'type' => 'date' ],
            'store_visit_status'        => [ 'label' => 'Show/No Show',         'type' => 'select', 'options' => [ 'Show','No Show' ] ],
            'attempt'                   => [ 'label' => 'Attempt (1-6)',        'type' => 'select', 'options' => [1,2,3,4,5,6] ],
            'attempt_type'              => [ 'label' => 'Attempt Type',         'type' => 'select', 'options' => [
                'Connected:Not Relevant','Connected:Relevant','Not Connected'
            ] ],
            'attempt_status'            => [ 'label' => 'Attempt Status',       'type' => 'select', 'options' => [
                'Call Rescheduled','Just browsing','Not Interested','Ringing / No Response','Store Visit Scheduled','Wrong Number / Invalid Number'
            ] ],
            'remarks'                   => [ 'label' => 'Remarks',              'type' => 'textarea' ],
		];

		/* Hooks ----------------------------------------------------------- */
		add_action( 'add_meta_boxes',        [ $this, 'register_metaboxes' ] );
        add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );

		add_action( 'save_post_lcm_campaign',[ $this, 'save_campaign' ], 10, 2 );
		add_action( 'save_post_lcm_lead', [ $this, 'save_lead' ], 10, 3 );

		/* Tiny JS for dynamic Adset fill --------------------------------- */
		add_action( 'admin_enqueue_scripts', function( $hook ){
			if ( in_array( get_current_screen()->post_type, [ 'lcm_lead' ], true ) ) {
                	$map = [];
	$campaigns = get_posts( [
		'post_type'   => 'lcm_campaign',
		'numberposts' => -1,
		'post_status' => 'publish',
	] );
	foreach ( $campaigns as $c ) {
		$map[ esc_js( $c->post_title ) ] = esc_js( $c->post_title ); // 1–1 mapping
	}

		wp_add_inline_script( 'jquery-core', '
	/* ----- Attempt → Attempt Type → Attempt Status flow ----- */
	function lcmUpdateAttemptUI(){
		const $attempt  = jQuery("#lcm_attempt");
		const $type     = jQuery("#lcm_attempt_type");
		const $status   = jQuery("#lcm_attempt_status");

		if ( !$attempt.val() ) {
			$type.prop("disabled", true).val("");
			$status.prop("disabled", true).val("");
		} else {
			$type.prop("disabled", false);
			if ( !$type.val() ) {
				$status.prop("disabled", true).val("");
			} else {
				$status.prop("disabled", false);
			}
		}
	}
	jQuery(document).on("change", "#lcm_attempt, #lcm_attempt_type", lcmUpdateAttemptUI);
	jQuery(lcmUpdateAttemptUI);  // run once on load
' );

			}
		});
        /* Recount if a Lead is trashed or deleted */
add_action( 'before_delete_post', function ( $post_id ) {
	if ( get_post_type( $post_id ) === 'lcm_lead' ) {
		global $wpdb;
		$adset = $wpdb->get_var( $wpdb->prepare(
			"SELECT adset FROM {$wpdb->prefix}lcm_leads WHERE post_id = %d",
			$post_id
		) );
		if ( $adset ) {
			$this->recount_campaign_counters( $adset );
		}
	}
}, 10, 1 );

	}

	/* ---------------------------------------------------------------------
	 * Meta-box registration
	 * ------------------------------------------------------------------ */
	public function register_metaboxes() {

		add_meta_box(
			'lcm_campaign_meta',
			'Campaign Details',
			[ $this, 'render_campaign_metabox' ],
			'lcm_campaign',
			'normal',
			'default'
		);

		add_meta_box(
			'lcm_lead_meta',
			'Lead Details',
			[ $this, 'render_lead_metabox' ],
			'lcm_lead',
			'normal',
			'default'
		);
	}

	/* ---------------------------------------------------------------------
	 * Renderers
	 * ------------------------------------------------------------------ */
	private function field_html( $key, $field, $value ) {

		$type  = $field['type'];
		$label = $field['label'];

		echo '<p><label for="lcm_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br/>';

		switch ( $type ) {

			case 'text':
			case 'email':
			case 'number':
			case 'date':
			case 'time':
				printf(
					'<input type="%s" id="lcm_%s" name="lcm[%s]" value="%s" %s step="%s" class="widefat" />',
					esc_attr( $type ),
					esc_attr( $key ),
					esc_attr( $key ),
					esc_attr( $value ),
					( ! empty( $field['readonly'] ) ? 'readonly' : '' ),
					isset( $field['step'] ) ? esc_attr( $field['step'] ) : ''
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="lcm_%s" name="lcm[%s]" rows="3" class="widefat">%s</textarea>',
					esc_attr( $key ),
					esc_attr( $key ),
					esc_textarea( $value )
				);
				break;

			/* -------- <select> ---------- */
case 'select':
	echo '<select id="lcm_' . esc_attr( $key ) . '" name="lcm[' . esc_attr( $key ) . ']" class="widefat">';
	echo '<option value="">— Select —</option>';             // <-- NEW
	foreach ( $field['options'] as $opt ) {
		printf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $opt ),
			selected( $value, $opt, false ),
			esc_html( $opt )
		);
	}
	echo '</select>';
	break;

/* -------- client dropdown ---------- */
case 'user-dropdown':
	echo '<select id="lcm_' . esc_attr( $key ) . '" name="lcm[' . esc_attr( $key ) . ']" class="widefat">';
	echo '<option value="">— Select —</option>';             // <-- NEW
	foreach ( get_users( [ 'role__in' => [ 'client' ], 'orderby' => 'display_name', 'order' => 'ASC' ] ) as $user ) {
		printf(
			'<option value="%d" %s>%s</option>',
			$user->ID,
			selected( $value, $user->ID, false ),
			esc_html( $user->display_name . " ({$user->user_email})" )
		);
	}
	echo '</select>';
	break;

/* -------- campaign dropdown ---------- */
case 'campaign-dropdown':
	echo '<select id="lcm_campaign_id" name="lcm[campaign_id]" class="widefat">';
	echo '<option value="">— Select —</option>';

	$campaigns = get_posts( [
		'post_type'   => 'lcm_campaign',
		'numberposts' => -1,
		'post_status' => 'publish',
	] );

	foreach ( $campaigns as $c ) {
		$adset = $c->post_title;               // ← was meta; now use title
		printf(
			'<option value="%d" data-adset="%s" %s>%s</option>',
			$c->ID,
			esc_attr( $adset ),
			selected( $value, $c->ID, false ),
			esc_html( $c->post_title )
		);
	}
	echo '</select>';
	break;


		}

		echo '</p>';
	}

	public function render_campaign_metabox( $post ) {

		// nonce
		wp_nonce_field( 'lcm_campaign_save', 'lcm_campaign_nonce' );

		// Pull existing row
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}lcm_campaigns WHERE post_id = %d",
			$post->ID
		), ARRAY_A );

		foreach ( $this->campaign_fields as $key => $config ) {
			$value = $row[ $key ] ?? '';
			$this->field_html( $key, $config, $value );
		}
	}

	public function render_lead_metabox( $post ) {

		wp_nonce_field( 'lcm_lead_save', 'lcm_lead_nonce' );

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}lcm_leads WHERE post_id = %d",
			$post->ID
		), ARRAY_A );

		foreach ( $this->lead_fields as $key => $config ) {
			$value = $row[ $key ] ?? '';
			$this->field_html( $key, $config, $value );
		}
	}

	/* ---------------------------------------------------------------------
	 * Save handlers
	 * ------------------------------------------------------------------ */
private function sanitize_array( $array ) : array {

	$out = [];

	foreach ( $array as $k => $v ) {

		// Skip nested arrays for now
		if ( is_array( $v ) ) {
			continue;
		}

		if ( is_email( $v ) ) {
			$out[ $k ] = sanitize_email( $v );

		} elseif ( is_numeric( $v ) ) {
			// Preserve int/float as real number
			$out[ $k ] = 0 + $v;

		} else {
			$out[ $k ] = sanitize_text_field( $v );
		}
	}

	return $out;
}
	
    public function save_campaign( $post_id, $post ) {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! isset($_POST['lcm_campaign_nonce']) || ! wp_verify_nonce($_POST['lcm_campaign_nonce'],'lcm_campaign_save') ) return;
        $data = $this->sanitize_array($_POST['lcm'] ?? []);
        if ( empty($data['campaign_title']) ) return; // required
        $data['post_id'] = $post_id;
        // Update post title
        wp_update_post([ 'ID'=>$post_id,'post_title'=>sanitize_text_field($data['campaign_title']) ]);
        // Write to table
        global $wpdb;
        $wpdb->replace("{$wpdb->prefix}lcm_campaigns", $data);
    }

    public function save_lead( $post_id, $post, $update ) {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! isset($_POST['lcm_lead_nonce']) || ! wp_verify_nonce($_POST['lcm_lead_nonce'],'lcm_lead_save') ) return;
        $data = $this->sanitize_array($_POST['lcm'] ?? []);
        if ( empty($data['lead_title']) ) return; // required
        $data['post_id'] = $post_id;
        // Update post title
        wp_update_post([ 'ID'=>$post_id,'post_title'=>sanitize_text_field($data['lead_title']) ]);
        // Write to table
        global $wpdb;
        $wpdb->replace("{$wpdb->prefix}lcm_leads", $data);
        // Recount
        if ( ! empty($data['adset']) ) {
            $this->recount_campaign_counters( $data['adset'] );
        }
    }
 
 /**
 * Re‐count just the total number of leads for a given campaign.
 *
 * @param int $campaign_id The post ID of the campaign (campaign_id in lcm_leads).
 */
public function recount_total_leads( int $campaign_id ) {
    global $wpdb;

    // Count all leads linked to this campaign_id
    $total_leads = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads
         WHERE campaign_id = %d",
        $campaign_id
    ) );

    // Update the 'leads' column in your campaigns table (mapped by post_id)
    $wpdb->update(
        $wpdb->prefix . 'lcm_campaigns',
        [ 'leads' => $total_leads ],
        [ 'post_id' => $campaign_id ],
        [ '%d' ],
        [ '%d' ]
    );
}

/**
 * Re‐count all call‐result tallies for a given campaign.
 *
 * @param int $campaign_id The post ID of the campaign (campaign_id in lcm_leads).
 */
public function recount_campaign_counters( int $campaign_id ) {
    global $wpdb;

    // 1) Total leads
    $total_leads = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads
         WHERE campaign_id = %d",
        $campaign_id
    ) );

    // 2) Group by attempt_type for the three key statuses
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT attempt_type, COUNT(*) AS qty
           FROM {$wpdb->prefix}lcm_leads
          WHERE campaign_id = %d
            AND attempt_type IN ('Connected:Not Relevant','Connected:Relevant','Not Connected')
          GROUP BY attempt_type",
        $campaign_id
    ), ARRAY_A );

    // initialize counters
    $connected_not_rel = 0;
    $connected_rel     = 0;
    $not_connected     = 0;

    foreach ( $rows as $r ) {
        switch ( $r['attempt_type'] ) {
            case 'Connected:Not Relevant':
                $connected_not_rel = (int) $r['qty'];
                break;
            case 'Connected:Relevant':
                $connected_rel     = (int) $r['qty'];
                break;
            case 'Not Connected':
                $not_connected     = (int) $r['qty'];
                break;
        }
    }
     // 4) Count “Store Visit Scheduled”
    $scheduled_store_visit = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads
         WHERE campaign_id = %d
           AND attempt_status = 'Store Visit Scheduled'",
        $campaign_id
    ) );

    // 5) Count actual Store Visits (“Show”)
    $store_visit = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}lcm_leads
         WHERE campaign_id = %d
           AND store_visit_status = 'Show'",
        $campaign_id
    ) );
    // 3) Combined connected = both “Connected” types
    $connected_total = $connected_not_rel + $connected_rel;

    // 4) N/A = any lead without one of those three statuses
    $not_available = max( 0,
        $total_leads
        - $connected_not_rel
        - $connected_rel
        - $not_connected
    );

    // 5) Push all fields back into campaigns table (matched by post_id)
    $wpdb->update(
        $wpdb->prefix . 'lcm_campaigns',
        [
            'leads'              => $total_leads,
            'connected_number'   => $connected_total,
            'not_connected'      => $not_connected,
            'relevant'           => $connected_rel,
            'scheduled_store_visit'  => $scheduled_store_visit,
            'store_visit'            => $store_visit,
            'not_available'      => $not_available,
        ],
        [ 'post_id' => $campaign_id ],
        [ '%d','%d','%d','%d','%d','%d','%d' ],
        [ '%d' ]
    );
}


    /** Change “Add title” placeholder for each CPT */
public function title_placeholder( $text, $post ) {
	if ( $post && $post->post_type === 'lcm_campaign' ) {
		return 'Adset';
	}
	if ( $post && $post->post_type === 'lcm_lead' ) {
		return 'UID';
	}
	return $text;
}

}

/* -------------------------------------------------------------------------
 * Bootstrap the admin UI for users who can manage posts
 * ---------------------------------------------------------------------- */
add_action( 'init', function () {
	if ( is_admin() && current_user_can( 'edit_posts' ) ) {
		new PPC_CRM_Admin_UI();
	}
} );