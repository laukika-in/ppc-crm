<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Admin_UI {

    public static function init() {
        // Add custom columns, filters, etc.
        add_filter( 'manage_lead_data_posts_columns',       [ __CLASS__, 'lead_columns' ] );
        add_action( 'manage_lead_data_posts_custom_column', [ __CLASS__, 'lead_column_data' ], 10, 2 );
        // Repeat for campaign_data...
    }

    public static function lead_columns( $cols ) {
        $cols = [
            'cb'      => $cols['cb'],
            'title'   => __( 'UID' ),
            'client'  => __( 'Client' ),
            'date'    => __( 'Date' ),
            'actions' => __( 'Actions' ),
        ];
        return $cols;
    }

    public static function lead_column_data( $col, $post_id ) {
        switch ( $col ) {
            case 'client':
                echo esc_html( get_the_author_meta( 'user_nicename', get_post_meta( $post_id, 'client', true ) ) );
                break;
            case 'date':
                echo esc_html( get_post_meta( $post_id, 'date_of_lead', true ) );
                break;
            case 'actions':
                printf(
                    '<a href="%1$s" target="_blank">Edit</a>',
                    esc_url( get_edit_post_link( $post_id ) )
                );
                break;
        }
    }
}
