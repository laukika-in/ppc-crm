<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Admin_UI {

    public static function init() {
        // Lead Data columns
        add_filter( 'manage_lead_data_posts_columns',       [ __CLASS__, 'lead_columns' ] );
        add_action( 'manage_lead_data_posts_custom_column', [ __CLASS__, 'lead_column_data' ], 10, 2 );

        // Campaign Data columns
        add_filter( 'manage_campaign_data_posts_columns',       [ __CLASS__, 'campaign_columns' ] );
        add_action( 'manage_campaign_data_posts_custom_column', [ __CLASS__, 'campaign_column_data' ], 10, 2 );
    }

    public static function lead_columns( $cols ) {
        return [
            'cb'      => $cols['cb'],
            'title'   => __( 'UID', 'ppc-crm' ),
            'client'  => __( 'Client', 'ppc-crm' ),
            'date'    => __( 'Date', 'ppc-crm' ),
            'actions' => __( 'Actions', 'ppc-crm' ),
        ];
    }

    public static function lead_column_data( $col, $post_id ) {
        switch ( $col ) {
            case 'client':
                $client_id = get_post_meta( $post_id, 'client', true );
                $client    = get_user_by( 'ID', $client_id );
                echo $client ? esc_html( $client->display_name ) : '';
                break;

            case 'date':
                echo esc_html( get_post_meta( $post_id, 'date_of_lead', true ) );
                break;

            case 'actions':
                printf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    esc_url( get_edit_post_link( $post_id ) ),
                    __( 'Edit', 'ppc-crm' )
                );
                break;
        }
    }

    public static function campaign_columns( $cols ) {
        return [
            'cb'      => $cols['cb'],
            'title'   => __( 'Campaign', 'ppc-crm' ),
            'client'  => __( 'Client', 'ppc-crm' ),
            'month'   => __( 'Month', 'ppc-crm' ),
            'actions' => __( 'Actions', 'ppc-crm' ),
        ];
    }

    public static function campaign_column_data( $col, $post_id ) {
        switch ( $col ) {
            case 'client':
                $client_id = get_post_meta( $post_id, 'client', true );
                $client    = get_user_by( 'ID', $client_id );
                echo $client ? esc_html( $client->display_name ) : '';
                break;

            case 'month':
                echo esc_html( get_post_meta( $post_id, 'month', true ) );
                break;

            case 'actions':
                printf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    esc_url( get_edit_post_link( $post_id ) ),
                    __( 'Edit', 'ppc-crm' )
                );
                break;
        }
    }
}

// Hook it up
add_action( 'init', [ Admin_UI::class, 'init' ] );
