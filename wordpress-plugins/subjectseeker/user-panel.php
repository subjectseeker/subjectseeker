<?php
/*
Plugin Name: SubjectSeeker User Panel
Plugin URI: http://scienceseeker.org/
Description: User panel for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssUserPanel')) {
  class ssUserPanel {
    function ssUserPanel() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssUserPanel($args) {
        extract($args);
        $options = get_option('widget_ssUserPanel');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssUserPanel();
        echo $after_widget;
      }
      function widget_ssUserPanel_control() {
        $options = get_option('widget_ssUserPanel');
        if ( $_POST['ssUserPanel-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssUserPanel-title']));
          update_option('widget_ssUserPanel', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssUserPanel-title">Title:<input class="widefat" name="ssUserPanel-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssUserPanel-submit" name="ssUserPanel-submit" value="1" />';
      }
      register_sidebar_widget('ssUserPanel', 'widget_ssUserPanel');
      register_widget_control('ssUserPanel', 'widget_ssUserPanel_control');
    }
  }
}

$ssUserPanel = new ssUserPanel();
add_action( 'plugins_loaded', array(&$ssUserPanel, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssUserPanel, 'setupActivation' ));

function get_ssUserPanel($settings = array()) {
  doUserPanel();
}

function doUserPanel() {
  $db = ssDbConnect();
	print "<div id=\"user-panel-container\">
	<ul class=\"user-panel\">";
  if (is_user_logged_in()){
		global $current_user;
		global $userProfile;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
		
		global $userBlogs;
		global $userPosts;
		print "<li class=\"panel-button\"><a href=\"$userProfile\">User Profile</a></li>
		<li class=\"panel-button\">".wp_loginout( $before = '', $after = '', $echo = true)." </li>
		<li class=\"panel-button\"><a href=\"$userBlogs\">Your blogs</a></li>
		<li class=\"panel-button\"><a href=\"$userPosts\">Your posts</a></li>
		<li class=\"panel-button\"><a href=\"$userPosts/?step=scan&scanNow=1&addPosts=1&n=10\">Scan your recent posts for citations</a></li>";
		if ($userPriv > 0){
			global $approveUrl;
			global $adminBlogs;
			print "<li class=\"panel-button\"><a href=\"$approveUrl\">Approve Blogs</a></li>
			<li class=\"panel-button\"><a href=\"$adminBlogs\">Administer Blogs</a></li>";
			if ($userPriv > 1){
				global $adminPosts;
				global $adminUsers;
				print "<li class=\"panel-button\"><a href=\"$adminPosts\">Administer Posts</a></li>";
				print "<li class=\"panel-button\"><a href=\"$adminUsers\">Administer Users</a></li>";
			}
		}
	}
	else {
		global $registerUrl;
		print "<li class=\"panel-button\"><a href=\"$registerUrl\">Register</a></li>
		<li class=\"panel-button\">".wp_loginout( $before = '', $after = '', $echo = true)." </li>";
	}
	print "</ul>
	</div>";
}
?>
