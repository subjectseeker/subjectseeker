<?php
/*
Plugin Name: SubjectSeeker Administration Panel
Plugin URI: http://scienceseeker.org/
Description: Administration Panel for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminPanel')) {
  class ssAdminPanel {
    function ssAdminPanel() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminPanel($args) {
        extract($args);
        $options = get_option('widget_ssAdminPanel');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminPanel();
        echo $after_widget;
      }
      function widget_ssAdminPanel_control() {
        $options = get_option('widget_ssAdminPanel');
        if ( $_POST['ssAdminPanel-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminPanel-title']));
          update_option('widget_ssAdminPanel', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminPanel-title">Title:<input class="widefat" name="ssAdminPanel-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminPanel-submit" name="ssAdminPanel-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminPanel', 'widget_ssAdminPanel');
      register_widget_control('ssAdminPanel', 'widget_ssAdminPanel_control');
    }
  }
}

$ssAdminPanel = new ssAdminPanel();
add_action( 'plugins_loaded', array(&$ssAdminPanel, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminPanel, 'setupActivation' ));

function get_ssAdminPanel($settings = array()) {
  global $ssAdminPanel;
  doAdminPanel();
}

function doAdminPanel() {
  $db = ssDbConnect();
  if (is_user_logged_in()){
    global $current_user;
		global $approveUrl;
		global $adminUsers;
		global $adminBlogs;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
		print "<p>Hello, $displayName.</p>\n";
		if ($userPriv > 0){
			print "<h2>Administration Tools</h2>
			<ul class=\"adminPanelOptions\">
			<h3><a href=\"$approveUrl\"><li class=\"adminPanelOption\">Approve Blogs</li></a></h3>
			<h3><a href=\"$adminBlogs\"><li class=\"adminPanelOption\">Administer Blogs</li></a></h3>";
			if ($userPriv > 1){
				print "<h3><a href=\"$adminUsers\"><li class=\"adminPanelOption\">Administer Users</li></a></h3>";
			}
			print "</ul>";
		}
		else { # not moderator or admin
  		print "You are not authorized to view the administration panel.<br />";
		}
	}
	else {
    print "Please log in.";
	}
}
?>