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
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;
    $userId = addUser($displayName, $email, $db);

    $userPriv = getUserPrivilegeStatus($userId, $db);

    print "<p>Hello, $displayName.</p>\n";
	
    if ($userPriv > 0) { // moderator or admin
    if ($step == null) {		
		print "<h2>List of pending blogs</h2>";
		displayEditPendingBlogs ($db);
			
    } else {
		
        print "<h2>Administrative action</h2>";
		
		$blogs["id"] = $_REQUEST["blogId"];
		$blogs["name"] = $_REQUEST["blogname"];
		$blogs["uri"] = $_REQUEST["blogurl"];
		$blogs["syndicationuri"] = $_REQUEST["blogsyndicationuri"];
		$blogs["description"] = $_REQUEST["blogdescription"];
		$blogs["topic1"] = $_REQUEST["topic1"];
		$blogs["topic2"] = $_REQUEST["topic2"];
		
		foreach ($blogs["id"] as $id => $value) {
			$blogId = stripslashes($blogs["id"][$id]);
			$blogname = stripslashes($blogs["name"][$id]);
			$blogurl = stripslashes($blogs["uri"][$id]);
			$blogsyndicationuri = stripslashes($blogs["syndicationuri"][$id]);
			$blogdescription = stripslashes($blogs["description"][$id]);
			$topic1 = stripslashes($blogs["topic1"][$id]);
			$topic2 = stripslashes($blogs["topic2"][$id]);
			$status = $_REQUEST["$blogId-blog"];
			
	  $result = checkBlogData ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayname, $db);
	  $oldBlogName = getBlogName($blogId, $db);
	  
	  if ($result == NULL) {
		   if ($status == 1) {
			   approveBlog($blogId, $db);
			   print "<p>Blog $blogname (id $blogId) APPROVED</p>\n";
			   } 
			elseif ($status == 2) {
				rejectBlog($blogId, $db);
				$contacts = getBlogContacts($blogId, $db);
				print "<p>Blog $oldBlogName (id $blogId) REJECTED (email contact(s):";
				foreach ($contacts as $contact) {
					print " <a href=\"mailto:$contact\">$contact</a>";
					}
					print ")</p>\n";
					}
					print "<p>$blogname (id $blogId) was updated.</p>";  
			} else {
				print "<p>$oldBlogName (id $blogId): <ul class=\"ss-error\">$result</ul></p>";
				}
			}
			$blogList = getPendingBlogs($db);
			if ($blogList != null) {
			return displayEditPendingBlogs ($db);
			}
      }
    } else { # not moderator or admin
      print "You are not authorized to view the list of blogs for approval.<br />";
    }
  } else {
    print "Please log in.";
  }
}
?>
