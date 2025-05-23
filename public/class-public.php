<?php
// public/class-public.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'lcm_lead_table', [ $this, 'shortcode_lead_table' ] );
	}

	/* ------------------------------------------------------------------ */
	public function register_assets() {

		$base = plugin_dir_url( dirname( __FILE__, 2 ) ); // …/ppc-crm/

		/* Bootstrap 5 (CDN) + jQuery (shipped with WP) */
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

		/* Our lead-table driver */
		wp_register_script(
			'lcm-lead-table',
			$base . 'public/assets/js/lead-table.js',
			[ 'jquery', 'bootstrap-js' ],
			PPC_CRM_VERSION,
			true
		);
	}

	/* ------------------------------------------------------------------ */
	public function shortcode_lead_table() : string {

		/* dropdown sources */
		$clients   = get_users( [ 'role__in' => [ 'client' ], 'fields'=>['ID','display_name'] ] );
		$campaigns = get_posts( [ 'post_type'=>'lcm_campaign', 'numberposts'=>-1, 'fields'=>'ids' ] );

		$js_vars = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'lcm_ajax' ),
			'clients'  => array_map( fn($u)=>[ $u->ID, $u->display_name ], $clients ),
			'ad_names' => array_map( fn($id)=>get_the_title($id), $campaigns ),
			'per_page' => 10,
		];

		/* enqueue */
		wp_enqueue_style( 'bootstrap-css' );
		wp_enqueue_script( 'lcm-lead-table' );
		wp_localize_script( 'lcm-lead-table', 'LCM', $js_vars );

		/* html skeleton */
		ob_start(); ?>
		<div class="lcm-table-wrapper card p-3">
			<div class="d-flex justify-content-between mb-2">
				<button class="btn btn-primary btn-sm" id="lcm-add-row">➕ Add Lead</button>
				<div id="lcm-pager" class="btn-group btn-group-sm"></div>
			</div>
			<div class="table-responsive">
				<table id="lcm-lead-table" class="table table-bordered table-sm align-middle">
					<thead class="table-light"></thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
