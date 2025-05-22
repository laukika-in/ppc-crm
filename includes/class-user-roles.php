<?php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class User_Roles {

    public static function register_roles() {
        add_role( 'client', 'Client', [
            'read'                       => true,
            'ppc_crm_view_own'           => true,
            'ppc_crm_edit_own'           => true,
        ] );
        add_role( 'ppc',    'PPC', [
            'read'                       => true,
            'ppc_crm_view_all'           => true,
            'ppc_crm_edit_all'           => true,
            'ppc_crm_manage_clients'     => true,
        ] );
    }

    public static function remove_roles() {
        remove_role( 'client' );
        remove_role( 'ppc' );
    }
}
