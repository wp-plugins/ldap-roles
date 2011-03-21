<?php 
/*
Plugin Name: LDAP Roles Plug-in
Plugin URI: http://www.w3.org
Description: A plugin to map LDAP groups to Wordpress roles
Version: 1.0
Author: Jean-Guilhem Rouel (http://www.w3.org/People/Jean-Gui),
*/

// Code from WPMU LDAP Plugin http://wpmuldap.tuxdocs.net/
function getGroups($server, $userDN) {
    //Make sure we're connected - we're not when this is called from the admin side
    if (!$server->connection_handle) {
	$server->dock();
    }

    // Get Groups
    $attributes_to_get = array(get_site_option('ldapAttributeDN',LDAP_DEFAULT_ATTRIBUTE_DN));
    if (get_site_option('ldapLinuxWindows')) {
	$search_filter = "(".get_site_option('ldapAttributeMemberNix',LDAP_DEFAULT_ATTRIBUTE_MEMBERNIX)."=$userDN)";
	$search_filter .= "(objectclass=".get_site_option('ldapAttributeGroupObjectclassNix',LDAP_DEFAULT_ATTRIBUTE_GROUP_OBJECTCLASSNIX).")";
    } else {
	$search_filter = "(".get_site_option('ldapAttributeMember',LDAP_DEFAULT_ATTRIBUTE_MEMBER)."=$userDN)";
	$search_filter .= "(objectclass=".get_site_option('ldapAttributeGroupObjectclass',LDAP_DEFAULT_ATTRIBUTE_GROUP_OBJECTCLASS).")";
    }
    $server->SetSearchCriteria("(&$search_filter)", $attributes_to_get);
    $server->Search();
    return ldap_get_entries($server->connection_handle, $server->search_result);
}

/**
 * Updates roles for a user on all blogs
 */
function updateUserRoles($user, $username, $password) {
    if (!is_a($user, 'WP_User')) {
        return $user;
    }
     
    $ldapString = wpmuSetupLdapOptions();
    $server = new LDAP_ro($ldapString);
    $server->DebugOff();
    
    // We need to bind the user to LDAP a second time to get the user DN
    $userDataArray = null;
    $result = $server->Authenticate ($username, $password, $userDataArray);

    if($result == LDAP_OK) {
        $userGroups = getGroups($server, $userDataArray[LDAP_INDEX_DN]);
    
        $ldapPriorities = get_site_option('ldapPriorities');
        $globalPerms = get_site_option('ldapPerms');
    
        // Update roles on each blog
        foreach(get_blog_list(0, 'all') as $blog) {
            $ldapPerms = get_blog_option($blog['blog_id'], 'ldapPerms');
            
            updateBlogUserRoles($blog['blog_id'], $user->ID, $userGroups, 
                                $ldapPerms, $globalPerms, 
                                $ldapPriorities);
        }
    }

    return $user;
}

/**
 * Update user's roles on a specific blog
 */
function updateBlogUserRoles($blog_id, $user_id, $user_groups, $perms, $globalPerms, $priorities) {
    if($priorities && $perms) {
        foreach($priorities as $priority => $roleKey) {
            foreach($user_groups as $g) {
                
                $group_dn = strtolower($g[get_site_option('ldapAttributeDN',LDAP_DEFAULT_ATTRIBUTE_DN)]);
                if($group_dn && 
                   (($perms[$roleKey] && in_array($group_dn, $perms[$roleKey]) || 
                     ($globalPerms[$roleKey] && in_array($group_dn, $globalPerms[$roleKey]))))) {
                    wpmuLdapAddUserToBlog($user_id, $blog_id, $roleKey);
                    return true;
                }
            }
        }
    }
    remove_user_from_blog($user_id, $blog_id);
    return false;
}