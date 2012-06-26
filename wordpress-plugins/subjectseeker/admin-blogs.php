<?php
/*
Plugin Name: SubjectSeeker Administer Blogs
Plugin URI: http://scienceseeker.org/
Description: Administer Blogs for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://scienceseeker.org/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminBlogs')) {
  class ssAdminBlogs {
    function ssAdminBlogs() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminBlogs($args) {
        extract($args);
        $options = get_option('widget_ssAdminBlogs');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminBlogs();
        echo $after_widget;
      }
      function widget_ssAdminBlogs_control() {
        $options = get_option('widget_ssAdminBlogs');
        if ( $_POST['ssAdminBlogs-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminBlogs-title']));
          update_option('widget_ssAdminBlogs', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminBlogs-title">Title:<input class="widefat" name="ssAdminBlogs-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminBlogs-submit" name="ssAdminBlogs-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminBlogs', 'widget_ssAdminBlogs');
      register_widget_control('ssAdminBlogs', 'widget_ssAdminBlogs_control');
    }
  }
}

$ssAdminBlogs = new ssAdminBlogs();
add_action( 'plugins_loaded', array(&$ssAdminBlogs, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminBlogs, 'setupActivation' ));

function get_ssAdminBlogs($settings = array()) {
  global $ssAdminBlogs;
  adminBlogs();
}

function adminBlogs() {
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
			$sort = $_REQUEST["sort"];
			$order = $_REQUEST["order"];
			$pagesize = $_REQUEST["n"];
			$offset = $_REQUEST["offset"];
			if (! $sort) {
				$sort = "alphabetical";
			}
			if (! $order) {
				$order = "asc";
			}
			if (! $pagesize) {
				$pagesize = "30";
			}
			if (! $offset) {
				$offset = "0";
			}
			print "<div class=\"toggle-button\">Display Options</div>
			<div class=\"ss-slide-wrapper\">
			<div class=\"ss-div-2\" id=\"filter-panel\">
			<form method=\"GET\">
			Sort by: <select name='sort'>\n
			<option value='id'";
			if ($sort == "id") {
				print " selected";
			}
			print ">ID</option>\n
			<option value='alphabetical'";
			if ($sort == "alphabetical") {
				print " selected";
			}
			print ">Name</option>\n
			<option value='added-date'";
			if ($sort == "added-date") {
				print " selected";
			}
			print ">Added Date</option>\n
			<option value='crawled-date'";
			if ($sort == "crawled-date") {
				print " selected";
			}
			print ">Crawled Date</option>\n</select> | <select name='order'>\n
			<option value='asc'";
			if ($order == "asc") {
				print " selected";
			}
			print ">Ascending</option>\n
			<option value='desc'";
			if ($order == "desc") {
				print " selected";
			}
			print ">Descending</option>\n
			</select><br />\n
			Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/> | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/><br />
			<input class=\"ss-button\" type=\"submit\" value=\"Go\" />
			</form>
			</div>
			</div>
			<br />";
				
			if ($step) {
				confirmEditBlog ($step, $userId, $userPriv, $db);
			}
			$queryList = httpParamsToSearchQuery();
			$settings = httpParamsToExtraQuery();
			$settings["show-all"] = "true";
			$settings["type"] = "blog";
			$blogData = generateSearchQuery ($queryList, $settings, 1, $errormsgs, $db);
			
			if ($blogData == NULL) {
				print "<p>There are no more blogs in the system.</p>";
			}
			else {
				print "<hr />";
				while ($row = mysql_fetch_array($blogData)) {
					editBlogForm ($row, $userPriv, NULL, $db);
				}
			}
			global $adminBlogs;
			// Buttons for pages
			pageButtons ($adminBlogs, $pagesize);
		} else { // not moderator or admin
			print "<p>You are not authorized to administrate blogs.</p>";
		}
  } else { // not logged in
    print "<p>Please log in.</p>";
  }
}
?>