<?php
// File: ppc-crm/includes/class-shortcodes.php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

    /**
     * Hook in shortcodes
     */
    public static function init() {
        add_shortcode( 'lead_data_table',     [ __CLASS__, 'render_lead_table' ] );
        add_shortcode( 'campaign_data_table', [ __CLASS__, 'render_campaign_table' ] );
    }

    /**
     * Render the Lead Data table container
     */
    public static function render_lead_table() {
        ob_start();
        ?>
        <div id="lead_data_table" class="ppc-crm-table"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the Campaign Data table container
     */
    public static function render_campaign_table() {
        ob_start();
        ?>
        <div id="campaign_data_table" class="ppc-crm-table"></div>
        <?php
        return ob_get_clean();
    }
}
