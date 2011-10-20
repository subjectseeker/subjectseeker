<?php
/*
Plugin Name: SubjectSeeker Administration Button
Plugin URI: http://scienceseeker.org/
Description: Administration button for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminButton')) {
  class ssAdminButton {
    function ssAdminButton() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminButton($args) {
        extract($args);
        $options = get_option('widget_ssAdminButton');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminButton();
        echo $after_widget;
      }
      function widget_ssAdminButton_control() {
        $options = get_option('widget_ssAdminButton');
        if ( $_POST['ssAdminButton-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminButton-title']));
          update_option('widget_ssAdminButton', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminButton-title">Title:<input class="widefat" name="ssAdminButton-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminButton-submit" name="ssAdminButton-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminButton', 'widget_ssAdminButton');
      register_widget_control('ssAdminButton', 'widget_ssAdminButton_control');
    }
  }
}

$ssAdminButton = new ssAdminButton();
add_action( 'plugins_loaded', array(&$ssAdminButton, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminButton, 'setupActivation' ));

function get_ssAdminButton($settings = array()) {
  doAdminButton();
}

function doAdminButton() {
  $db = ssDbConnect();
  if (is_user_logged_in()){
    global $current_user;
		global $adminPanel;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
		if ($userPriv > 0){
			print "<div id=\"adminButton\">
			<a href=\"$adminPanel\">Administration Panel</a>
			</div>";
		}
	}
}
?>
