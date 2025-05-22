<?php
// includes/class-init.php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

use PPC_CRM\DB;

class Init {
    /** @var Init */
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    private function setup() {
        // Register custom roles
        User_Roles::register_roles();

        // Register custom post types
        add_action( 'init', [ Post_Types::class, 'register' ] );

        // Register meta boxes
        Meta_Boxes::register();

        // Initialize access control
        Access_Control::init();

        // Initialize AJAX handlers
        Ajax_Handlers::init();

        // Initialize shortcodes
        Shortcodes::init();

        // Initialize admin UI enhancements
        Admin_UI::init();

        // Enqueue scripts/styles
        add_action( 'wp_enqueue_scripts',    [ Assets::class, 'enqueue_frontend' ] );
        add_action( 'admin_enqueue_scripts', [ Assets::class, 'enqueue_backend' ] );
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Create custom DB tables
        DB::create_tables();

        // Ensure CPT registration and flush rewrite rules
        Post_Types::register();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        flush_rewrite_rules();
        User_Roles::remove_roles();
    }
}
