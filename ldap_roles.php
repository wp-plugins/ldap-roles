<?php 
/*
Plugin Name: LDAP Roles Plug-in
Plugin URI: http://www.w3.org
Description: A plugin to map LDAP groups to Wordpress roles. This plugin depends on and reuses parts of WPMU LDAP Plugin http://wpmuldap.tuxdocs.net/
Version: 1.0
Author: Jean-Guilhem Rouel (http://www.w3.org/People/Jean-Gui),
*/

if (get_site_option("ldapAuth")) {
    require_once('ldap_roles/lib/ldap_roles.php');
    require_once('ldap_roles/lib/ldap_roles_admin.php');
    
    // Authentication hook
    add_action('authenticate', 'updateUserRoles', 26, 3);

    // Admin hooks
    add_action('admin_init', 'ldap_roles_admin_init');
    add_action('admin_menu', 'ldapAddPermsMenus');
}
