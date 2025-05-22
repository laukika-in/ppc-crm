<?php
// File: ppc-crm/includes/class-user-roles.php
namespace PPC_CRM;

defined( 'ABSPATH' ) || exit;

class User_Roles {

    /**
     * Register Client and PPC roles with custom capabilities
     */
    public static function register_roles() {
        add_role( 'client', __( 'Client', 'ppc-crm' ), [
            'read'                 => true,
            'ppc_crm_view_own'     => true,
            'ppc_crm_edit_own'     => true,
            'ppc_crm_load_own'     => true,
        ] );

        add_role( 'ppc', __( 'PPC', 'ppc-crm' ), [
            'read'                 => true,
            'ppc_crm_view_all'     => true,
            'ppc_crm_edit_all'     => true,
            'ppc_crm_load_all'     => true,
            'ppc_crm_manage_clients'=> true,
        ] );
    }

    /**
     * Remove roles on deactivation/uninstall
     */
    public static function remove_roles() {
        remove_role( 'client' );
        remove_role( 'ppc' );
    }
}
