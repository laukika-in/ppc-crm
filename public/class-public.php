<?php
// public/class-public.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Public  {

	public function __construct() {

		/* Shortcodes */
		add_shortcode( 'lcm_campaign_table', [ $this, 'sc_campaign_table' ] );
		add_shortcode( 'lcm_lead_table',     [ $this, 'sc_lead_table' ] );

		/* Front-end assets  */
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	/* ---------------------------------------------------------------------
	 * Register but don’t enqueue globally
	 * ------------------------------------------------------------------ */
	public function register_assets() {

		// Tabulator core (CDN)
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

		// Plugin-specific initialisers
		wp_register_script(
			'lcm-tabulator-campaign',
			plugins_url( 'public/assets/js/tabulator-init-campaign.js', dirname( __FILE__ ) ),
			[ 'tabulator-js' ],
			PPC_CRM_Plugin::VERSION,
			true
		);
		wp_register_script(
			'lcm-tabulator-lead',
			plugins_url( 'public/assets/js/tabulator-init-lead.js', dirname( __FILE__ ) ),
			[ 'tabulator-js' ],
			PPC_CRM_Plugin::VERSION,
			true
		);

		// Optional CSS overrides
		wp_register_style(
			'lcm-tabulator-tweaks',
			plugins_url( 'public/assets/css/tabulator-tweaks.css', dirname( __FILE__ ) ),
			[ 'tabulator-css' ],
			PPC_CRM_Plugin::VERSION
		);
	}

	/* ---------------------------------------------------------------------
	 *  Shortcode outputs + targeted enqueue
	 * ------------------------------------------------------------------ */
	public function sc_campaign_table() {

		$this->enqueue_for( 'campaign' );

		ob_start();
		?>
		<div id="lcm-campaign-tbl"></div>
		<?php
		return ob_get_clean();
	}

	public function sc_lead_table() {

		$this->enqueue_for( 'lead' );

		ob_start();
		?>
		<div id="lcm-lead-tbl"></div>
		<?php
		return ob_get_clean();
	}

	/* --------------------------------------------------------------------- */
	private function enqueue_for( $which ) {

        // in class-public.php -> enqueue()
wp_enqueue_style( 'tabulator-css', 'https://unpkg.com/tabulator-tables@6.2.0/dist/css/tabulator.min.css', [], '6.2.0' );
wp_enqueue_script( 'tabulator-js', 'https://unpkg.com/tabulator-tables@6.2.0/dist/js/tabulator.min.js', [], '6.2.0', true );

		// Core CSS/JS
		wp_enqueue_style( 'tabulator-css' );
		wp_enqueue_style( 'lcm-tabulator-tweaks' );
		wp_enqueue_script( 'tabulator-js' );

		// Choose correct init file
		if ( 'campaign' === $which ) {
			wp_enqueue_script( 'lcm-tabulator-campaign' );
			$handle = 'lcm-tabulator-campaign';
			$action = 'lcm_get_campaigns_json';
		} else {
			wp_enqueue_script( 'lcm-tabulator-lead' );
			$handle = 'lcm-tabulator-lead';
			$action = 'lcm_get_leads_json';
		}
wp_localize_script(
    'lcm-tabulator-campaign',
    'LCM',
    [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lcm_ajax' ),
			'action'   => $action,
    ]
); 
	}
}
