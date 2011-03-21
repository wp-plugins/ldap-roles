<?php 
/*
Plugin Name: LDAP Roles Plug-in
Plugin URI: http://www.w3.org
Description: A plugin to map LDAP groups to Wordpress roles
Version: 1.0
Author: Jean-Guilhem Rouel (http://www.w3.org/People/Jean-Gui),
*/

function ldap_roles_admin_init() {
    wp_register_script('ldap_roles_admin', WPMU_PLUGIN_URL . '/ldap_roles/public/ldap_roles_admin.js');
}


function ldapAddPermsMenus() {
    $objCurrUser = wp_get_current_user();
    $objUser = wp_cache_get($objCurrUser->id, 'users');
    if (is_super_admin($objUser->user_login)) {
        $page = add_options_page('LDAP Permissions', 'LDAP Permissions', 
                         'manage_options', 'ldapperms', 'ldapPermsPanel');
        add_action('admin_print_scripts-' . $page, 'ldap_roles_admin_styles');

        $page = add_submenu_page('wpmu-admin.php', 'LDAP Groups to Wordpress Roles Mapping', 
                                 'LDAP Roles Mapping', 9, basename(__FILE__), 'ldapMappingConfPanel');
        add_action('admin_print_scripts-' . $page, 'ldap_roles_admin_styles');
    }
}

function ldap_roles_admin_styles() {
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('ldap_roles_admin');

    wp_admin_css('nav-menu', "/wp-admin/css/nav-menu$suffix.css");
}


function ldapMappingConfPanel() {
    global $current_blog;

    // Process POST Updates
    if ($_SERVER['REQUEST_METHOD'] == 'POST') ldapGlobalPermsSave();
    
    $tab = $_GET['tab'];
    $allowedtabs = array('general','connection','attributes','updates');
    ?>
<div class="wrap">
 <?php 
      ldapGlobalPermsPanel();
?>
</div>
<?php
}

function ldapGlobalPermsSave() {
    function textareaToArray($text) {
        return array_map('trim', explode("\n", trim($text)));
    }
    $ldapPerms = array_map('textareaToArray', $_POST['ldapPerms']);
    update_site_option('ldapPerms', $ldapPerms);
    
    $ldapPriorities = array_map('trim', $_POST['ldapPriorities']);
    function emptyRole($val) {
        if($val === null || $val === '') {
            return 99999;
        }
        return $val;
    }
    $ldapPriorities = array_map('emptyRole', $ldapPriorities);
    asort($ldapPriorities, SORT_NUMERIC);
    update_site_option('ldapPriorities', array_keys($ldapPriorities));
    
    echo "<div id='message' class='updated fade'><p>Priorities and Mappings Saved!</p></div>";
}

function ldapGlobalPermsPanel() {
    $ldapPerms = get_site_option('ldapPerms');
    $ldapPriorities = get_site_option('ldapPriorities');
    $roles = get_editable_roles();
    foreach($roles as $krole => $role) {
        if(!in_array($krole, $ldapPriorities)) {
            $ldapPriorities[] = $krole;
        }
    }
?>
  <form method="post" id="ldap_auth_groups" action="ms-admin.php?page=ldap_roles_admin.php">
    <h3>LDAP Permissions Settings</h3>
    <p>This form allows you to map LDAP groups to Wordpress roles globally (ie. for all blogs) and define priorities between roles. 
    In the expandable boxes below, enter the full dn to each group.  For multiple groups, enter each group on a new line. You can also sort the boxes
    by drag and drop. Sorting roles is important in case a user belongs to several LDAP groups (as a user can be assigned only one WordPress role).</p>
      <ul id="sortable">
      <?php foreach($ldapPriorities as $priority => $key): ?>
        <li class="menu-item menu-item-depth-0 menu-item-category">
          <dl class="menu-item-bar">
            <dt class="menu-item-handle">
              <span class="item-title">
                <label for="ldapPerms_<?php echo $key ?>"><span><?php echo $priority+1 ?></span>. <?php echo $roles[$key]['name'] ?></label>
              </span>

              <span class="item-controls">
                <span class="item-order">
                  <input class="priority" style="width:30px" type="text" name="ldapPriorities[<?php echo $key ?>]" id="ldapPriorities_<?php echo $key ?>" value="<?php echo $priority ?>"/>
                </span>
                <a href="#" title="Edit Menu Item" class="item-edit">Edit Menu Item</a>
              </span>
            </dt>
          </dl>
          <div class="menu-item-settings">
            <textarea style="width: 390px" rows="3" cols="54" name="ldapPerms[<?php echo $key ?>]" id="ldapPerms_<?php echo $key ?>"><?php echo @implode("\n", $ldapPerms[$key]) ?></textarea>
          </div>
        </li>
		  <?php endforeach ?>
      </ul>
    <p class="submit"><input type="submit" name="ldapPermsSave" value="Save Priorities" /></p>
	</form>
<?php
}


function ldapPermsPanel() {
    global $blog_id;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['ldapPermsSave']) {
        function textareaToArray($text) {
            return array_map('trim', explode("\n", trim($text)));
        }
        $ldapPerms = array_map('textareaToArray', $_POST['ldapPerms']);
        update_blog_option($blog_id, 'ldapPerms', $ldapPerms);

        echo "<div id='message' class='updated fade'><p>Saved Options!</p></div>";
    }

    $ldapPerms = get_blog_option($blog_id, 'ldapPerms');

    $ldapPriorities = get_site_option('ldapPriorities');
    $roles = get_editable_roles();
    foreach($roles as $krole => $role) {
        if(!in_array($krole, $ldapPriorities)) {
            $ldapPriorities[] = $krole;
        }
    }
?>
        <form method="post" id="ldap_auth_groups" action="options-general.php?page=ldapperms">
          <h3>LDAP Permissions Settings</h3>
          <p>
            This page allows to add LDAP group to WordPress role mappings for the blog &quot;<?php echo get_blog_option($blog_id, 'blogname') ?>&quot;.
            In the expandable boxes below, enter the full dn of each group. For multiple groups, enter each group on a new line.
          </p>
      <ul>
      <?php foreach($ldapPriorities as $priority => $key): ?>
        <li class="menu-item menu-item-depth-0 menu-item-category">
          <dl class="menu-item-bar">
            <dt class="menu-item-handle">
              <span class="item-title">
                <label for="ldapPerms_<?php echo $key ?>"><span><?php echo $priority+1 ?></span>. <?php echo $roles[$key]['name'] ?></label>
              </span>
              <span class="item-controls">
                <a href="#" title="Edit Menu Item" class="item-edit">Edit Menu Item</a>
              </span>
            </dt>
          </dl>
          <div class="menu-item-settings">
            <textarea style="width: 390px" rows="3" cols="54" name="ldapPerms[<?php echo $key ?>]" id="ldapPerms_<?php echo $key ?>"><?php echo @implode("\n", $ldapPerms[$key]) ?></textarea>
          </div>
        </li>
		  <?php endforeach ?>
      </ul>
      <p class="submit"><input type="submit" name="ldapPermsSave" value="Save Groups" /></p>
	</form>
<?php
}
