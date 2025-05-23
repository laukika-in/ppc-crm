<?php
// public/class-public.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'lcm_campaign_table', [ $this, 'sc_campaign_table' ] );
		add_shortcode( 'lcm_lead_table',     [ $this, 'sc_lead_table' ] );
	}

	/* -------------------------------------------------------- */
	public function register_assets() {

	
		$base_url = plugin_dir_url( __FILE__ ); // …/ppc-crm/

		/* Luxon (needed for Tabulator datetime formatter) */
		wp_register_script(
			'luxon-js',
			'https://cdnjs.cloudflare.com/ajax/libs/luxon/3.4.0/luxon.min.js',
			[],
			'3.4.0',
			true
		);

		/* Tabulator core (depends on Luxon) */
		wp_register_style(
			'tabulator-css',
			'https://unpkg.com/tabulator-tables@6.2.0/dist/css/tabulator.min.css',
			[],
			'6.2.0'
		);
		wp_register_script(
			'tabulator-js',
			'https://unpkg.com/tabulator-tables@6.2.0/dist/js/tabulator.min.js',
			[ 'luxon-js' ],
			'6.2.0',
			true
		);

		/* Style tweaks */
		wp_register_style(
			'lcm-tabulator-tweaks',
			$base_url . 'assets/css/tabulator-tweaks.css',
			[ 'tabulator-css' ],
			PPC_CRM_VERSION
		);

		/* Init scripts */
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

	/* -------------------------------------------------------- */
	public function sc_campaign_table() {
		return $this->render_table_shortcode( 'campaign' );
	}

	public function sc_lead_table() {
		return $this->render_table_shortcode( 'lead' );
	}

	/* -------------------------------------------------------- */
	private function render_table_shortcode( string $which ) : string {

		/* Dynamic dropdown data */
		$clients   = get_users( [ 'role__in' => [ 'client' ], 'fields' => [ 'ID', 'display_name' ] ] );
		$campaigns = get_posts( [ 'post_type' => 'lcm_campaign', 'numberposts' => -1, 'fields' => 'ids' ] );

		$vars = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'action'   => ( $which === 'campaign' ? 'lcm_get_campaigns_json' : 'lcm_get_leads_json' ),
			'nonce'    => wp_create_nonce( 'lcm_ajax' ),
			'clients'  => array_map( fn($u)=>[ $u->ID, $u->display_name ], $clients ),
			'ad_names' => array_map( fn($id)=>get_the_title( $id ), $campaigns ),
		];

		// Assets
		wp_enqueue_style( 'tabulator-css' );
		wp_enqueue_style( 'lcm-tabulator-tweaks' );
		wp_enqueue_script( 'tabulator-js' );

		if ( $which === 'campaign' ) {
			wp_enqueue_script( 'lcm-tabulator-campaign' );
		} else {
			wp_enqueue_script( 'lcm-tabulator-lead' );
		}

		// HTML output
		$div_id = $which === 'campaign' ? 'lcm-campaign-tbl' : 'lcm-lead-tbl';

		return '<script>window.LCM=' . wp_json_encode( $vars ) . ';</script>'
		     . '<div class="lcm-table-wrapper">'
		     .   '<div id="' . esc_attr( $div_id ) . '"></div>'
		     . '</div>';
	}
}
