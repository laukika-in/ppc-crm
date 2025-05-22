<?php
defined( 'ABSPATH' ) || exit;
$current_user = wp_get_current_user();
?>
<div class="ppc-crm-lead-wrapper">
    <?php if ( in_array( 'ppc', (array) $current_user->roles, true ) ) : ?>
        <label for="ppc_crm_lead_client_filter"><?php esc_html_e( 'Filter by Client:', 'ppc-crm' ); ?></label>
        <select id="ppc_crm_lead_client_filter">
            <option value=""><?php esc_html_e( 'All Clients', 'ppc-crm' ); ?></option>
            <?php
            $clients = get_users( [ 'role' => 'client' ] );
            foreach ( $clients as $client ) {
                printf(
                    '<option value="%1$d">%2$s</option>',
                    esc_attr( $client->ID ),
                    esc_html( $client->display_name )
                );
            }
            ?>
        </select>
    <?php endif; ?>
<button id="ppc_crm_add_lead" class="button">
    <?php esc_html_e( 'Add New Lead', 'ppc-crm' ); ?>
</button>
<div id="lead_data_table" class="ppc-crm-table"></div>
</div>
