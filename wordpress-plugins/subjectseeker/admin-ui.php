<?php
/*
Plugin Name: SubjectSeeker Administration UI
Plugin URI: http://scienceseeker.org/
Description: Administration UI for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminUI')) {
  class ssAdminUI {
    function ssAdminUI() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminUI($args) {
        extract($args);
        $options = get_option('widget_ssAdminUI');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminUI();
        echo $after_widget;
      }
      function widget_ssAdminUI_control() {
        $options = get_option('widget_ssAdminUI');
        if ( $_POST['ssAdminUI-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminUI-title']));
          update_option('widget_ssAdminUI', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminUI-title">Title:<input class="widefat" name="ssAdminUI-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminUI-submit" name="ssAdminUI-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminUI', 'widget_ssAdminUI');
      register_widget_control('ssAdminUI', 'widget_ssAdminUI_control');
    }
  }
}

$ssAdminUI = new ssAdminUI();
add_action( 'plugins_loaded', array(&$ssAdminUI, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminUI, 'setupActivation' ));

function get_ssAdminUI($settings = array()) {
  global $ssAdminUI;
  doAdminUI();
}

function doAdminUI() {
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
			<h3><li class=\"adminPanelOption\"><a href=\"$approveUrl\">Approve Blogs</a></li></h3>
			<h3><li class=\"adminPanelOption\"><a href=\"$adminBlogs\">Blogs Administration</a></li></h3>";
			if ($userPriv > 1){
				print "<h3><li class=\"adminPanelOption\"><a href=\"$adminUsers\">Users Administration</a></li></h3>";
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