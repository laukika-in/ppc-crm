<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'lcm_lead_table',  [ $this, 'shortcode_lead_table' ] );
	}

	/* ------------------------------------------------------------------ */
	public function register_assets() {

		$base = plugin_dir_url( dirname( __FILE__, 2 ) ); // …/plugins/ppc-crm/

		wp_register_style(
			'bootstrap-css',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
			[], '5.3.3'
		);
		wp_register_script(
			'bootstrap-js',
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
			[ 'jquery' ], '5.3.3', true
		);

		wp_register_script(
			'lcm-lead-table',
			$base . 'public/assets/js/lead-table.js',
			[ 'jquery', 'bootstrap-js' ],
			PPC_CRM_VERSION,
			true
		);

		/* tiny css tweak for nowrap cells */
		wp_add_inline_style( 'bootstrap-css', '.lcm-nowrap td, .lcm-nowrap th{white-space:nowrap}' );
	}

	/* ------------------------------------------------------------------ */
	public function shortcode_lead_table() : string {

		$clients   = get_users( [ 'role__in' => [ 'client' ], 'fields' => [ 'ID','display_name' ] ] );
		$campaigns = get_posts( [ 'post_type' => 'lcm_campaign', 'numberposts' => -1, 'fields' => 'ids' ] );

		$vars = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'lcm_ajax' ),
			'clients'  => array_map( fn($u)=>[ $u->ID, $u->display_name ], $clients ),
			'adsets'   => array_map( fn($id)=>get_the_title( $id ), $campaigns ),
			'per_page' => 10,
		];

		wp_enqueue_style( 'bootstrap-css' );
		wp_enqueue_script( 'lcm-lead-table' );
		wp_localize_script( 'lcm-lead-table', 'LCM', $vars );

		ob_start(); ?>
		<div class="card p-3 shadow-sm">
			<div class="d-flex justify-content-between mb-2">
				<button id="lcm-add-row" class="btn btn-primary btn-sm">➕ Add Lead</button>
				<div id="lcm-pager" class="btn-group btn-group-sm"></div>
			</div>

			<div class="table-responsive lcm-nowrap">
				<table id="lcm-lead-table" class="table table-sm table-bordered align-middle mb-0 w-100">
					<thead class="table-light"></thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
