<?php
// core/class-plugin.php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/* Front-end / Ajax layers */
require_once plugin_dir_path( __FILE__ ) . '/../public/class-public.php';
require_once plugin_dir_path( __FILE__ ) . '/../public/class-ajax.php';

/**
 * Core singleton – registers roles, CPTs, DB schema
 */
class PPC_CRM_Plugin {

	private static $instance = null;
	const VERSION = '0.2.0';

	/* -----------------------------------------------------------------------
	 * Singleton loader
	 * -------------------------------------------------------------------- */
	public static function instance() {
		return self::$instance ?: ( self::$instance = new self );
	}

	/* -----------------------------------------------------------------------
	 * Constructor – hooks that must run on every page load
	 * -------------------------------------------------------------------- */
	private function __construct() {

		// CPTs / roles
		add_action( 'init', [ $this, 'register_roles' ] );
		add_action( 'init', [ $this, 'register_cpts' ] );
new PPC_CRM_Public();
new PPC_CRM_Ajax();

		// Keep Clients + PPC out of wp-admin
		add_action( 'admin_init', [ $this, 'maybe_block_backend' ] );
	}

	/* -----------------------------------------------------------------------
	 * Lifecycle
	 * -------------------------------------------------------------------- */
	public static function activate() {
		self::instance()->create_tables();
		self::instance()->register_roles(); // run once in case site never hit “init”
	}

	public static function deactivate() { /* nothing yet */ }

	/* -----------------------------------------------------------------------
	 * Roles
	 * -------------------------------------------------------------------- */
	public function register_roles() {

		add_role(
			'client',
			'Client',
			[ 'read' => true ]          // no edit caps
		);

		add_role(
			'ppc',
			'PPC',
			[ 'read' => true ]          // no edit caps
		);
	}

	public function maybe_block_backend() {
		if ( is_admin()
		     && ! wp_doing_ajax()
		     && ( current_user_can( 'client' ) || current_user_can( 'ppc' ) )
		) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/* -----------------------------------------------------------------------
	 * Custom post types
	 * -------------------------------------------------------------------- */
	public function register_cpts() {

		// Campaign Data (post title = Campaign Name)
		register_post_type( 'lcm_campaign',
			[
				'label'               => 'Campaign Data',
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 25,
				'supports'            => [ 'title' ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);

		// Lead Data (post title = UID)
		register_post_type( 'lcm_lead',
			[
				'label'               => 'Lead Data',
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 26,
				'supports'            => [ 'title' ],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			]
		);
	}

	/* -----------------------------------------------------------------------
	 * Database
	 * -------------------------------------------------------------------- */
	private function create_tables() {

		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$campaigns = $wpdb->prefix . 'lcm_campaigns';
		$leads     = $wpdb->prefix . 'lcm_leads';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Campaigns -----------------------------------------------------------------
		dbDelta("
			CREATE TABLE $campaigns (
				id                     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id                BIGINT(20) UNSIGNED NOT NULL,
				client_id              BIGINT(20) UNSIGNED NOT NULL,
				month                  VARCHAR(20) NOT NULL,
				week                   DECIMAL(4,1) NOT NULL,
				campaign_date          DATE NOT NULL,
				location               VARCHAR(255) NOT NULL,
				campaign_name          VARCHAR(255) NOT NULL,
				adset                  VARCHAR(255) NOT NULL,
				leads                  INT UNSIGNED DEFAULT 0,
				reach                  INT UNSIGNED DEFAULT 0,
				impressions            INT UNSIGNED DEFAULT 0,
				cost_per_lead          DECIMAL(12,2) DEFAULT 0,
				amount_spent           DECIMAL(12,2) DEFAULT 0,
				cpm                    DECIMAL(12,2) DEFAULT 0,
				connected_number       INT UNSIGNED DEFAULT 0,
				not_connected          INT UNSIGNED DEFAULT 0,
				relevant               INT UNSIGNED DEFAULT 0,
				not_available          INT UNSIGNED DEFAULT 0,
				scheduled_store_visit  INT UNSIGNED DEFAULT 0,
				store_visit            INT UNSIGNED DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY client_id (client_id),
				KEY campaign_name (campaign_name(191))
			) $charset;
		");

		// Leads ---------------------------------------------------------------------
		dbDelta("
			CREATE TABLE $leads (
				id                       BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id                  BIGINT(20) UNSIGNED NOT NULL,
				client_id                BIGINT(20) UNSIGNED NOT NULL,
				campaign_id              BIGINT(20) UNSIGNED NOT NULL,
				ad_name                  VARCHAR(255) NOT NULL,
				adset                    VARCHAR(255) NOT NULL,
				uid                      VARCHAR(255) NOT NULL,
				lead_date                DATE NOT NULL,
				lead_time                TIME NOT NULL,
				day                      VARCHAR(10)  NOT NULL,
				name                     VARCHAR(255) NOT NULL,
				phone_number             VARCHAR(20)  NOT NULL,
				alt_number               VARCHAR(20)  NOT NULL,
				email                    VARCHAR(255) NOT NULL,
				location                 VARCHAR(255) NOT NULL,
				client_type              VARCHAR(20)  NOT NULL,
				sources                  VARCHAR(255) NOT NULL,
				source_of_campaign       VARCHAR(255) NOT NULL,
				targeting_of_campaign    VARCHAR(255) NOT NULL,
				budget                   VARCHAR(50)  NOT NULL,
				product_looking_to_buy   VARCHAR(255) NOT NULL,
				occasion                 VARCHAR(50)  NOT NULL,
				for_whom                 VARCHAR(255) NOT NULL,
				final_type               VARCHAR(255) NOT NULL,
				final_sub_type           VARCHAR(255) NOT NULL,
				main_city                VARCHAR(255) NOT NULL,
				store_location           VARCHAR(255) NOT NULL,
				store_visit              DATE         NOT NULL,
				store_visit_status       VARCHAR(255) NOT NULL,
				attempt                  TINYINT      NOT NULL,
				attempt_type             VARCHAR(50)  NOT NULL,
				attempt_status           VARCHAR(80)  NOT NULL,
				remarks                  TEXT         NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY client_id  (client_id),
				KEY campaign_id (campaign_id),
				KEY ad_name (ad_name(191))
			) $charset;
		");
	}
}
