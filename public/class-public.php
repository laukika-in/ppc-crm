<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PPC_CRM_Public {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
		add_shortcode( 'lcm_lead_table',     [ $this, 'shortcode_lead_table' ] );
		add_shortcode( 'lcm_campaign_table', [ $this, 'shortcode_campaign_table' ] );
	}

	/* ------------------------------------------------------------------ */
	public function register_assets() {

		$base = plugin_dir_url( __FILE__ );          // …/ppc-crm/public/

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

		/* Lead grid (already working) */
		wp_register_script(
			'lcm-lead-table',
			$base . 'assets/js/lead-table.js',
			[ 'jquery', 'bootstrap-js' ],
			PPC_CRM_VERSION,
			true
		);

		/* NEW Campaign grid */
		wp_register_script(
			'lcm-campaign-table',
			$base . 'assets/js/campaign-table.js',
			[ 'jquery', 'bootstrap-js' ],
			PPC_CRM_VERSION,
			true
		);

		/* nowrap for all tables */
		wp_add_inline_style( 'bootstrap-css',
			'.lcm-nowrap td,.lcm-nowrap th{white-space:nowrap} .lcm-scroll{max-width:100%;overflow-x:auto}'
		);
	}

	/* ------------------------------------------------------------------ */
	public function shortcode_lead_table() {
		return $this->render_table( 'lead' );
	}
	public function shortcode_campaign_table() {
		return $this->render_table( 'campaign' );
	}

	/* ------------------------------------------------------------------ */
	private function render_table( string $which ) : string {

		// Dropdown sources
		$clients = get_users( [ 'role__in'=>['client'], 'fields'=>['ID','display_name'] ] );
		$campaigns = get_posts( [ 'post_type'=>'lcm_campaign','numberposts'=>-1,'fields'=>'ids' ] );

		$vars = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'lcm_ajax' ),
			'per_page' => 10,

			'clients'  => array_map( fn($u)=>[ $u->ID,$u->display_name ], $clients ),
			'adsets'   => array_map( fn($id)=>get_the_title($id), $campaigns ),
		];

		wp_enqueue_style( 'bootstrap-css' );
		if ( $which === 'lead' ) {
			wp_enqueue_script( 'lcm-lead-table' );
			wp_localize_script( 'lcm-lead-table', 'LCM', $vars );
		} else {
			wp_enqueue_script( 'lcm-campaign-table' );
			wp_localize_script( 'lcm-campaign-table', 'LCM', $vars );
		}

		$div = $which === 'lead' ? 'lcm-lead-table' : 'lcm-campaign-table';

		ob_start(); ?>
		<div class="card p-3 shadow-sm mb-4">
			<div class="d-flex justify-content-between mb-2">
				<button id="lcm-add-row-<?=esc_attr($which);?>" class="btn btn-primary btn-sm">➕ Add <?=ucfirst($which);?></button>
				<div id="lcm-pager-<?=esc_attr($which);?>" class="btn-group btn-group-sm"></div>
			</div>

			<div class="table-responsive lcm-scroll lcm-nowrap">
				<table id="<?=esc_attr($div);?>" class="table table-sm table-bordered align-middle mb-0 w-100" style="table-layout:auto">
					<thead class="table-light"></thead>
					<tbody></tbody>
				</table>
			</div>
		</div>

		<!-- Shared delete modal -->
		<div class="modal fade" id="lcmDelModal" tabindex="-1">
		  <div class="modal-dialog modal-dialog-centered">
		    <div class="modal-content">
		      <div class="modal-header"><h5 class="modal-title">Delete Row</h5></div>
		      <div class="modal-body">Are you sure you want to delete this row?</div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
		        <button type="button" class="btn btn-danger btn-sm" id="lcm-confirm-del">Delete</button>
		      </div>
		    </div>
		  </div>
		</div>
		<?php
		return ob_get_clean();
	}
}
