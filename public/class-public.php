<?php
// public/class-public.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Front-end layer
 *  – Registers Tabulator assets
 *  – Provides two shortcodes:
 *      [lcm_campaign_table]
 *      [lcm_lead_table]
 */
class PPC_CRM_Public {

	/* ---------------------------------------------------------------------
	 * Constructor – hooks
	 * ------------------------------------------------------------------ */
	public function __construct() {

		// Only register; enqueue lazily when a shortcode is used
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

		// Shortcodes
		add_shortcode( 'lcm_campaign_table', [ $this, 'sc_campaign_table' ] );
		add_shortcode( 'lcm_lead_table',     [ $this, 'sc_lead_table' ] );
	}

	/* ---------------------------------------------------------------------
	 * 1) Register Tabulator + plugin scripts / styles
	 * ------------------------------------------------------------------ */
	public function register_assets() {

		$base_url = plugin_dir_url( __FILE__ ); // …/ppc-crm/

    /* 0) Luxon – required by Tabulator’s datetime formatter ----------- */
    wp_register_script(
        'luxon-js',
        'https://cdnjs.cloudflare.com/ajax/libs/luxon/3.4.0/luxon.min.js',
        [],
        '3.4.0',
        true
    );

    /* 1) Tabulator core (now depends on luxon-js) ---------------------- */
    wp_register_style(
        'tabulator-css',
        'https://unpkg.com/tabulator-tables@6.2.0/dist/css/tabulator.min.css',
        [],
        '6.2.0'
    );
    wp_register_script(
        'tabulator-js',
        'https://unpkg.com/tabulator-tables@6.2.0/dist/js/tabulator.min.js',
        [ 'luxon-js' ],        // <— new dependency
        '6.2.0',
        true
    );
		/* Optional tweaks */
		wp_register_style(
			'lcm-tabulator-tweaks',
			$base_url . 'assets/css/tabulator-tweaks.css',
			[ 'tabulator-css' ],
			PPC_CRM_VERSION
		);

		/* Init scripts that build each table (depend on Tabulator core) */
		wp_register_script(
			'lcm-tabulator-campaign',
			$base_url . 'assets/js/tabulator-init-campaign.js',
			[ 'tabulator-js' ],
			PPC_CRM_VERSION,
			true
		);

		wp_register_script(
			'lcm-tabulator-lead',
			$base_url . 'assets/js/tabulator-init-lead.js',
			[ 'tabulator-js' ],
			PPC_CRM_VERSION,
			true
		);
	}

	/* ---------------------------------------------------------------------
	 * 2) Shortcodes
	 * ------------------------------------------------------------------ */
public function sc_campaign_table() {

	$vars = [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'action'   => 'lcm_get_campaigns_json',
		'nonce'    => wp_create_nonce( 'lcm_ajax' ),
	];

	$this->enqueue_for( 'campaign' );

return '<script>window.LCM=' . wp_json_encode( $vars ) . ';</script>'
     . '<div class="lcm-table-wrapper">'
     .   '<div id="lcm-lead-tbl"></div>'
     . '</div>';

}

public function sc_lead_table() {

	$vars = [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'action'   => 'lcm_get_leads_json',
		'nonce'    => wp_create_nonce( 'lcm_ajax' ),
	];

	$this->enqueue_for( 'lead' );

	return '<script>window.LCM=' . wp_json_encode( $vars ) . ';</script>'
	     . '<div id="lcm-lead-tbl"></div>';
}

	/* ---------------------------------------------------------------------
	 * 3) Helper: enqueue correct assets + localise Ajax vars
	 * ------------------------------------------------------------------ */
	/* ---------------------------------------------------------------------
 * 3) Helper: enqueue correct assets + localise Ajax vars
 * ------------------------------------------------------------------ */
private function enqueue_for( string $which ) : void {

	// Core + tweaks (unchanged)
	wp_enqueue_style( 'tabulator-css' );
	wp_enqueue_style( 'lcm-tabulator-tweaks' );
	wp_enqueue_script( 'tabulator-js' );

	// Decide which init file we’ll load
	if ( 'campaign' === $which ) {
		$init_handle = 'lcm-tabulator-campaign';
		$action      = 'lcm_get_campaigns_json';
	} else {
		$init_handle = 'lcm-tabulator-lead';
		$action      = 'lcm_get_leads_json';
	}

	/* ----  NEW: inject the LCM object BEFORE any init scripts run ---- */
	$vars_js = sprintf(
		'window.LCM = %s;',
		wp_json_encode( [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'action'   => $action,
			'nonce'    => wp_create_nonce( 'lcm_ajax' ),
		] )
	);
	// Attach to Tabulator core (prints immediately because it’s already queued)
	wp_add_inline_script( 'tabulator-js', $vars_js, 'after' );

	/* Finally queue the init file itself */
	wp_enqueue_script( $init_handle );
}

}
