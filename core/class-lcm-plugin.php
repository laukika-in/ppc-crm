<?php
// core/class-lcm-plugin.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LCM_Plugin {

    private static $instance = null;
    const VERSION = '0.1.0';

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'register_roles' ) );
        add_action( 'init', array( $this, 'register_cpts' ) );
    }

    public function activate() {
        $this->create_tables();
        $this->register_roles();
    }

    public function deactivate() { /* cleanup if needed */ }

    private function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $table_campaigns = $wpdb->prefix . 'lcm_campaigns';
        $table_leads     = $wpdb->prefix . 'lcm_leads';

        $sql = "
        CREATE TABLE IF NOT EXISTS {$table_campaigns} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ad_name varchar(255) NOT NULL,
            client_id bigint(20) unsigned NOT NULL,
            data longtext,
            PRIMARY KEY  (id),
            KEY ad_name (ad_name)
        ) {$charset};

        CREATE TABLE IF NOT EXISTS {$table_leads} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ad_name varchar(255) NOT NULL,
            client_id bigint(20) unsigned NOT NULL,
            campaign_id bigint(20) unsigned NOT NULL,
            data longtext,
            PRIMARY KEY  (id),
            KEY ad_name (ad_name)
        ) {$charset};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public function register_roles() {
        add_role( 'client', 'Client', array( 'read' => true ) );
        add_role( 'ppc',    'PPC',    array( 'read' => true ) );
    }

    public function register_cpts() {
        register_post_type( 'lcm_campaign',
            array(
                'label'   => 'Campaign Data',
                'public'  => false,
                'show_ui' => true,
                'supports'=> array( 'title' ),
                'capability_type' => 'post',
                'map_meta_cap'    => true,
            )
        );
        register_post_type( 'lcm_lead',
            array(
                'label'   => 'Lead Data',
                'public'  => false,
                'show_ui' => true,
                'supports'=> array( 'title' ),
                'capability_type' => 'post',
                'map_meta_cap'    => true,
            )
        );
    }
}
?>
