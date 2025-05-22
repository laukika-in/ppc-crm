<?php
// core/class-plugin.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * PPC-CRM main singleton.
 *  – Registers roles, CPTs, custom tables
 *  – Blocks Clients/PPC from wp-admin
 *  – Boots front-end shortcodes + Ajax feeders
 */
class PPC_CRM_Plugin {

	/** @var self|null */
	private static $instance = null;

public static function version() : string {
		return defined( 'PPC_CRM_VERSION' ) ? PPC_CRM_VERSION : '0.0.0';
	}
	/* ---------------------------------------------------------------------
	 * Singleton loader
	 * ------------------------------------------------------------------ */
	public static function instance() : self {
		return self::$instance ?: ( self::$instance = new self );
	}

	/* ---------------------------------------------------------------------
	 * Constructor
	 * ------------------------------------------------------------------ */
	private function __construct() {

		/* 1) Core hooks common to front + back */
		add_action( 'init', [ $this, 'register_roles' ] );
		add_action( 'init', [ $this, 'register_cpts' ] );
		add_action( 'admin_init', [ $this, 'maybe_block_backend' ] );

		/* 2) Boot admin UI (file already loaded from main plugin) */
		// noop – bootstrap handled in ppc-crm.php

		/* 3) Boot front-end classes
		 *    Require files here so they are available on every request
		 *    (both wp-admin and front).
		 */
		require_once dirname( __DIR__ ) . '/public/class-public.php';
		require_once dirname( __DIR__ ) . '/public/class-ajax.php';

		new PPC_CRM_Public();  // shortcodes + assets
		new PPC_CRM_Ajax();    // JSON feeders
	}

	/* ---------------------------------------------------------------------
	 * Activation / de-activation
	 * ------------------------------------------------------------------ */
	public static function activate() : void {
		self::instance()->create_tables();
		self::instance()->register_roles();   // ensure roles exist even if init not hit
	}

	public static function deactivate() : void {
		// Currently nothing to clean up on de-activation
	}

	/* ---------------------------------------------------------------------
	 * Roles
	 * ------------------------------------------------------------------ */
	public function register_roles() : void {

		add_role( 'client', 'Client', [ 'read' => true ] );
		add_role( 'ppc',    'PPC',    [ 'read' => true ] );
	}

	/** Redirect Clients / PPC away from wp-admin */
	public function maybe_block_backend() : void {
		if ( is_admin()
		     && ! wp_doing_ajax()
		     && ( current_user_can( 'client' ) || current_user_can( 'ppc' ) )
		) {
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/* ---------------------------------------------------------------------
	 * Custom Post Types
	 * ------------------------------------------------------------------ */
	public function register_cpts() : void {

		// Campaign Data (title = Adset)
		register_post_type( 'lcm_campaign', [
			'label'           => 'Campaign Data',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 25,
			'supports'        => [ 'title' ],
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		] );

		// Lead Data (title = UID)
		register_post_type( 'lcm_lead', [
			'label'           => 'Lead Data',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 26,
			'supports'        => [ 'title' ],
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		] );
	}

	/* ---------------------------------------------------------------------
	 * Database schema (custom tables)
	 * ------------------------------------------------------------------ */
	private function create_tables() : void {

		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$campaigns = $wpdb->prefix . 'lcm_campaigns';
		$leads     = $wpdb->prefix . 'lcm_leads';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Campaigns table
		dbDelta("
			CREATE TABLE $campaigns (
				id                     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id                BIGINT(20) UNSIGNED NOT NULL,
				client_id              BIGINT(20) UNSIGNED NOT NULL,
				month                  VARCHAR(20) NOT NULL,
				week                   DECIMAL(4,1) NOT NULL,
				campaign_date          DATE NOT NULL,
				location               VARCHAR(255) NOT NULL,
				adset                  VARCHAR(255) NOT NULL,
				leads                  INT UNSIGNED DEFAULT 0,
				reach                  INT UNSIGNED DEFAULT 0,
				impressions            INT UNSIGNED DEFAULT 0,
				cost_per_lead          DECIMAL(30,10) DEFAULT 0,
				amount_spent           DECIMAL(30,10) DEFAULT 0,
				cpm                    DECIMAL(30,10) DEFAULT 0,
				connected_number       INT UNSIGNED DEFAULT 0,
				not_connected          INT UNSIGNED DEFAULT 0,
				relevant               INT UNSIGNED DEFAULT 0,
				not_available          INT UNSIGNED DEFAULT 0,
				scheduled_store_visit  INT UNSIGNED DEFAULT 0,
				store_visit            INT UNSIGNED DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY client_id (client_id),
				KEY adset (adset(191))
			) $charset;
		");

		// Leads table
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
				store_visit_status       VARCHAR(10)  NOT NULL,
				attempt                  TINYINT      NOT NULL,
				attempt_type             VARCHAR(30)  NOT NULL,
				attempt_status           VARCHAR(80)  NOT NULL,
				remarks                  TEXT         NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id),
				KEY client_id  (client_id),
				KEY campaign_id (campaign_id),
				KEY adset (adset(191))
			) $charset;
		");
	}
}
