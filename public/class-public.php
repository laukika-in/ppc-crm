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

		/* Tabulator core (CDN) */
		wp_register_style(
			'tabulator-css',
			'https://unpkg.com/tabulator-tables@6.2.0/dist/css/tabulator.min.css',
			[],
			'6.2.0'
		);
		wp_register_script(
			'tabulator-js',
			'https://unpkg.com/tabulator-tables@6.2.0/dist/js/tabulator.min.js',
			[],
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

		$this->enqueue_for( 'campaign' );

		ob_start();
		echo '<div id="lcm-campaign-tbl"></div>';
		return ob_get_clean();
	}

	public function sc_lead_table() {

		$this->enqueue_for( 'lead' );

		ob_start();
		echo '<div id="lcm-lead-tbl"></div>';
		return ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * 3) Helper: enqueue correct assets + localise Ajax vars
	 * ------------------------------------------------------------------ */
	private function enqueue_for( string $which ) : void {

		// Core & tweaks
		wp_enqueue_style( 'tabulator-css' );
		wp_enqueue_style( 'lcm-tabulator-tweaks' );
		wp_enqueue_script( 'tabulator-js' );
 
	/* 2) Decide which init script and action */
	if ( 'campaign' === $which ) {
		$handle = 'lcm-tabulator-campaign';
		$action = 'lcm_get_campaigns_json';
	} else {
		$handle = 'lcm-tabulator-lead';
		$action = 'lcm_get_leads_json';
	}
		// Pass Ajax URL + nonce
			wp_localize_script( $handle, 'LCM', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'action'   => $action,
		'nonce'    => wp_create_nonce( 'lcm_ajax' ),
	] );
    wp_enqueue_script( $handle );
	}
}
