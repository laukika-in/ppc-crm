<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class DB {

    /**
     * Creates or updates the custom tables on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Lead Data table
        $sql_leads = "
        CREATE TABLE {$wpdb->prefix}ppc_crm_lead_data (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            uid varchar(100)          NOT NULL,
            date_of_lead date         NOT NULL,
            time_of_lead time         NOT NULL,
            day varchar(10)           NOT NULL,
            name varchar(150)         NOT NULL,
            phone varchar(20)         NOT NULL,
            alt_phone varchar(20)     NULL,
            email varchar(100)        NULL,
            location varchar(150)     NULL,
            client_type varchar(50)   NOT NULL,
            sources text              NULL,
            source_campaign text      NULL,
            targeting text            NULL,
            ad_name varchar(150)      NULL,
            adset varchar(150)        NULL,
            budget decimal(12,2)      NULL,
            product text              NULL,
            occasion varchar(50)      NULL,
            for_whom varchar(100)     NULL,
            final_type varchar(100)   NULL,
            final_subtype varchar(100)NULL,
            main_city varchar(100)    NULL,
            store_location varchar(150) NULL,
            store_visit date          NULL,
            store_visit_status varchar(50) NULL,
            attempts tinyint unsigned NOT NULL DEFAULT 1,
            attempt_type varchar(50)  NULL,
            attempt_status varchar(50) NULL,
            remarks text              NULL,
            PRIMARY KEY  (id),
            KEY client_id (client_id)
        ) $charset_collate;
        ";

        // Campaign Data table
        $sql_campaigns = "
        CREATE TABLE {$wpdb->prefix}ppc_crm_campaign_data (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            client_id bigint(20) unsigned NOT NULL,
            month varchar(10)         NOT NULL,
            week decimal(4,1)         NOT NULL,
            date date                 NOT NULL,
            location varchar(150)     NULL,
            campaign_name varchar(150)NULL,
            adset varchar(150)        NULL,
            leads int unsigned        NULL,
            reach int unsigned        NULL,
            impressions bigint unsigned NULL,
            cost_per_lead decimal(12,2) NULL,
            amount_spent decimal(12,2)  NULL,
            cpm decimal(12,2)           NULL,
            connected_number int unsigned NULL,
            not_connected int unsigned    NULL,
            relevant int unsigned         NULL,
            na_count int unsigned         NULL,
            scheduled_store_visit int unsigned NULL,
            store_visits int unsigned     NULL,
            PRIMARY KEY  (id),
            KEY client_id (client_id)
        ) $charset_collate;
        ";

        dbDelta( $sql_leads );
        dbDelta( $sql_campaigns );
    }
}
