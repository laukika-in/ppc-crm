<?php
class PPC_CRM_Roles {
    public static function init() {
        /* nothing runtime yet */
    }

    public static function activate() {
        add_role( 'ppc', __( 'PPC', 'ppc-crm' ), [
            'read'         => true,
            'edit_posts'   => true,
            'edit_others_posts' => true,
        ] );

        add_role( 'client', __( 'Client', 'ppc-crm' ), [
            'read' => true,
        ] );
    }

    public static function deactivate() {
        // keep roles so data remains accessible
    }
}