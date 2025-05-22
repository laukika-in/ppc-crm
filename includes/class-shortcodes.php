<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    public static function init() {
        add_shortcode( 'lead_data_table',     [ __CLASS__, 'lead_table' ] );
        add_shortcode( 'campaign_data_table', [ __CLASS__, 'campaign_table' ] );
    }

    public static function lead_table() {
        ob_start();
        include PPC_CRM_DIR . 'templates/shortcode-lead-data.php';
        return ob_get_clean();
    }

    public static function campaign_table() {
        ob_start();
        include PPC_CRM_DIR . 'templates/shortcode-campaign-data.php';
        return ob_get_clean();
    }
}
