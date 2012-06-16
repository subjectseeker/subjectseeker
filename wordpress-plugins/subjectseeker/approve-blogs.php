<?php
/*
Plugin Name: SubjectSeeker Approve Blogs
Plugin URI: http://scienceseeker.org/
Description: Approve blogs for SubjectSeeker tool
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssApproveBlogs')) {
  class ssApproveBlogs {
    function ssApproveBlogs() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssApproveBlogs($args) {
        extract($args);
        $options = get_option('widget_ssApproveBlogs');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssApproveBlogs();
        echo $after_widget;
      }
      function widget_ssApproveBlogs_control() {
        $options = get_option('widget_ssApproveBlogs');
        if ( $_POST['ssApproveBlogs-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssApproveBlogs-title']));
          update_option('widget_ssApproveBlogs', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssApproveBlogs-title">Title:<input class="widefat" name="ssApproveBlogs-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssApproveBlogs-submit" name="ssApproveBlogs-submit" value="1" />';
      }
      register_sidebar_widget('ssApproveBlogs', 'widget_ssApproveBlogs');
      register_widget_control('ssApproveBlogs', 'widget_ssApproveBlogs_control');
    }
  }
}

$ssApproveBlogs = new ssApproveBlogs();
add_action( 'plugins_loaded', array(&$ssApproveBlogs, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssApproveBlogs, 'setupActivation' ));

function get_ssApproveBlogs($settings = array()) {
  global $ssApproveBlogs;
  doApproveBlogs();
}

function doApproveBlogs() {
  $step = $_REQUEST["step"];
  $db = ssDbConnect();

  if (is_user_logged_in()){
    global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);
    $userPriv = getUserPrivilegeStatus($userId, $db);
	
    if ($userPriv > 0) { // moderator or admin
			if ($step == null) {		
				print "<h2>List of Pending Blogs</h2>";
			} 
			else {
				print "<h2>Administrative action</h2>";
				confirmEditBlog ($step, $userId, $userPriv, $db);
			}
			print "<form method=\"POST\">\n
			<input type=\"hidden\" name=\"step\" value=\"edit\" />";
			parse_str("type=blog&filter0=status&value0=1&sort=added-date&order=desc", $parsedQuery);
			$queryList = httpParamsToSearchQuery($parsedQuery);
			$settings = httpParamsToExtraQuery($parsedQuery);
			$settings["type"] = "blog";
			$blogData = generateSearchQuery ($queryList, $settings, $userPriv, $errormsgs, $db);
			
			if ($blogData) {
				print "<hr />";
				while ($row = mysql_fetch_array($blogData)) {
					editBlogForm ($row, $userPriv, "open", $db);
				}
			}
			else {
				print "<div id=\"padding-content\">There are no sources pending approval.</div>";
			}
    } else { # not moderator or admin
      print "You are not authorized to view the list of blogs for approval.<br />";
    }
  } else {
    print "Please log in.";
  }
}
?>
