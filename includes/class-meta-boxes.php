<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class Meta_Boxes {

    public static function register() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post',       [ __CLASS__, 'save_meta_boxes' ], 10, 2 );
    }

    public static function add_meta_boxes() {
        foreach ( [ 'lead_data', 'campaign_data' ] as $screen ) {
            add_meta_box(
                "ppc_crm_{$screen}_details",
                esc_html__( ucfirst( str_replace( '_', ' ', $screen ) ) . ' Details', 'ppc-crm' ),
                [ __CLASS__, 'render_meta_box' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public static function render_meta_box( $post ) {
        // Security
        wp_nonce_field( 'ppc_crm_save_' . $post->post_type, 'ppc_crm_meta_nonce' );
        // Load existing meta
        $meta = get_post_meta( $post->ID );
        // Include your template file:
        include PPC_CRM_DIR . "templates/{$post->post_type}-meta.php";
    }

    public static function save_meta_boxes( $post_id, $post ) {
        // Verify nonce
        if ( empty( $_POST['ppc_crm_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['ppc_crm_meta_nonce'], 'ppc_crm_save_' . $post->post_type ) ) {
            return;
        }

        // Don't save during autosave or revision
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Define sanitize callbacks based on post type
        $fields = [];
        if ( 'lead_data' === $post->post_type ) {
            $fields = [
                'client'              => 'intval',
                'uid'                 => 'sanitize_text_field',
                'date_of_lead'        => 'sanitize_text_field',
                'time_of_lead'        => 'sanitize_text_field',
                'day'                 => 'sanitize_text_field',
                'name'                => 'sanitize_text_field',
                'phone'               => 'sanitize_text_field',
                'alt_phone'           => 'sanitize_text_field',
                'email'               => 'sanitize_email',
                'location'            => 'sanitize_text_field',
                'client_type'         => 'sanitize_text_field',
                'sources'             => 'sanitize_textarea_field',
                'source_campaign'     => 'sanitize_textarea_field',
                'targeting'           => 'sanitize_textarea_field',
                'ad_name'             => 'sanitize_text_field',
                'adset'               => 'sanitize_text_field',
                'budget'              => 'sanitize_text_field',
                'product'             => 'sanitize_textarea_field',
                'occasion'            => 'sanitize_text_field',
                'for_whom'            => 'sanitize_text_field',
                'final_type'          => 'sanitize_text_field',
                'final_subtype'       => 'sanitize_text_field',
                'main_city'           => 'sanitize_text_field',
                'store_location'      => 'sanitize_text_field',
                'store_visit'         => 'sanitize_text_field',
                'store_visit_status'  => 'sanitize_text_field',
                'attempts'            => 'intval',
                'attempt_type'        => 'sanitize_text_field',
                'attempt_status'      => 'sanitize_text_field',
                'remarks'             => 'sanitize_textarea_field',
            ];
        } elseif ( 'campaign_data' === $post->post_type ) {
            $fields = [
                'client'               => 'intval',
                'month'                => 'sanitize_text_field',
                'week'                 => 'sanitize_text_field',
                'date'                 => 'sanitize_text_field',
                'location'             => 'sanitize_text_field',
                'campaign_name'        => 'sanitize_text_field',
                'adset'                => 'sanitize_text_field',
                'leads'                => 'intval',
                'reach'                => 'intval',
                'impressions'          => 'intval',
                'cost_per_lead'        => 'sanitize_text_field',
                'amount_spent'         => 'sanitize_text_field',
                'cpm'                  => 'sanitize_text_field',
                'connected_number'     => 'intval',
                'not_connected'        => 'intval',
                'relevant'             => 'intval',
                'na_count'             => 'intval',
                'scheduled_store_visit'=> 'intval',
                'store_visits'         => 'intval',
            ];
        }

        // Save each field
        foreach ( $fields as $key => $sanitize_cb ) {
            if ( isset( $_POST[ $key ] ) ) {
                $value = call_user_func( $sanitize_cb, $_POST[ $key ] );
                update_post_meta( $post_id, $key, $value );
            }
        }
    }
}

// Hook it up
add_action( 'init', [ Meta_Boxes::class, 'register' ] );
