<?php
/*
Plugin Name: SubjectSeeker Administrate Blogs
Plugin URI: http://scienceseeker.org/
Description: Administrate Blogs for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com
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
  doAdminBlogs();
}

function doAdminBlogs() {
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
				$arrange = $_REQUEST["arrange"];
				$order = $_REQUEST["order"];
				if ($arrange == null) {
					$arrange = "BLOG_NAME";
				}
				if ($order == null) {
					$order = "ASC";
				}
				print "<form method=\"POST\">\n";
				print "<input type=\"hidden\" name=\"filters\" value=\"filters\" />";
				print "Order by: ";
				print "<select name='arrange'>\n";
				print "<option value='BLOG_ID'";
				if ($arrange == "BLOG_ID") {
					print " selected";
				}
				print ">Id</option>\n";
				print "<option value='BLOG_STATUS_ID'";
				if ($arrange == "BLOG_STATUS_ID") {
					print " selected";
				}
				print ">Status</option>\n";
				print "<option value='BLOG_NAME'";
				if ($arrange == "BLOG_NAME") {
					print " selected";
				}
				print ">Name</option>\n";
				print "<option value='BLOG_URI'";
				if ($arrange == "BLOG_URI") {
					print " selected";
				}
				print ">URI</option>\n";
				print "<option value='BLOG_SYNDICATION_URI'";
				if ($arrange == "BLOG_SYNDICATION_URI") {
					print " selected";
				}
				print ">Syndication URI</option>\n";
				print "<option value='ADDED_DATE_TIME'";
				if ($arrange == "ADDED_DATE_TIME") {
					print " selected";
				}
				print ">Added Date</option>\n";
				print "<option value='CRAWLED_DATE_TIME'";
				if ($arrange == "CRAWLED_DATE_TIME") {
					print " selected";
				}
				print ">Crawled Date</option>\n";
				print "</select>\n";
				print "<select name='order'>\n";
				print "<option value='ASC'";
				if ($order == "ASC") {
					print " selected";
				}
				print ">Ascendant</option>\n";
				print "<option value='DESC'";
				if ($order == "DESC") {
					print " selected";
				}
				print ">Descendant</option>\n";
				print "</select>\n";
				print "<input type=\"submit\" value=\"Filter\" />";
				print "</form><br />";
			if ($step != null) {
				$blogId = stripslashes($_REQUEST["blogId"]);
				$blogname = stripslashes($_REQUEST["blogname"]);
				$blogurl = stripslashes($_REQUEST["blogurl"]);
				$blogsyndicationuri = stripslashes($_REQUEST["blogsyndicationuri"]);
				$blogdescription = stripslashes($_REQUEST["blogdescription"]);
				$topic1 = stripslashes($_REQUEST["topic1"]);
				$topic2 = stripslashes($_REQUEST["topic2"]);
				$blogStatus = stripslashes($_REQUEST["blogStatus"]);
				$blogDelete = stripslashes($_REQUEST["blogDelete"]);
				$oldBlogName = getBlogName($blogId, $db);
				editBlog ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $blogDelete, $displayname, $db);
				editBlogStatus ($blogId, $blogStatus, $db);
				$result = editBlog($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $blogDelete, $displayname, $db);
				if ($result == NULL) {					
					if ($blogStatus == 2 || $blogStatus == 4) {
						$contacts = getBlogContacts($blogId, $db);
						print "<p>Blog $oldBlogName (id $blogId) REJECTED (email contact(s):";
						foreach ($contacts as $contact) {
							print " <a href=\"mailto:$contact\">$contact</a>";
						}
						print ")</p>\n";
					}
					print "<p>$blogname (id $blogId) was updated.</p>";  
					} 
				else {
					print "<p><font color='red'>$oldBlogName (id $blogId): $result</font></p>";
				}
			}
		$bloglist = getBlogs($arrange, $order, $db);
		foreach ($bloglist as $blog) {
			$blogId = $blog["id"];
			$blogName = $blog["name"];
			$blogUri = $blog["uri"];
			$blogDescription = $blog["blogdescription"];
			$blogSyndicationUri = $blog["syndicationuri"];
			$blogtopics = getBlogTopics($blogId, $db);
			//$topic1 = $_REQUEST["topic1"];
			//$topic2 = $_REQUEST["topic2"];
			print "<p>$blogId - $blogName <a id=\"showForm-$blogId\" href=\"javascript:;\" onmousedown=\"toggleSlide('blogForm-$blogId');\" onclick=\"toggleButton('showForm-$blogId');\">Show</a></p>";
			print "<div id=\"blogForm-$blogId\" style=\"display:none; overflow:hidden; height:700px;\">";
			print "<form method=\"POST\">\n";
			print "<input type=\"hidden\" name=\"step\" value=\"edit\" />";
			if ($errormsg !== null) {
				print "<p><font color='red'>Error: $errormsg</font></p>\n";
			}
			print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";
			print "<p><strong>$blogName</strong></p>";
			print "<p>*Required field</p>\n<p>\n";
			print "*Blog name: <input type=\"text\" name=\"blogname\" size=\"40\" value=\"$blogName\"/><br />\n";
			print "*<a href=\"$blogUri\" style=\"none\" target=\"_blank\">Blog URL:</a> <input type=\"text\" name=\"blogurl\" size=\"40\" value=\"$blogUri\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)";
			print "</p><p>*<a href=\"$blogSyndicationUri\" style=\"none\" target=\"_blank\">Blog syndication URL:</a> <input type=\"text\" name=\"blogsyndicationuri\" size=\"40\" value=\"$blogSyndicationUri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)";
			print "</p><p>Blog description:<br /><textarea name=\"blogdescription\" rows=\"5\" cols=\"70\">$blogDescription</textarea><br />\n";
			print "Blog topics: <select name='topic1'>\n";
			print "<option value='-1'>None</option>\n";
			$topicList = getTopicList(true, $db);
			while ($row = mysql_fetch_array($topicList)) {
				print "<option value='" . $row["TOPIC_ID"] . "'";
				if ($row["TOPIC_ID"] == $blogtopics[0]) {
					print " selected";
				}
				print ">" . $row["TOPIC_NAME"] . "</option>\n";
			}
			print "</select>&nbsp;<select name='topic2'>\n";
			print "<option value='-1'> None</option>\n";
			$topicList = getTopicList(true, $db);
			while ($row = mysql_fetch_array($topicList)) {
				print "<option value='" . $row["TOPIC_ID"] . "'";
				if ($row["TOPIC_ID"] == $blogtopics[1]) {
					print " selected";
							}
				print ">" . $row["TOPIC_NAME"] . "</option>\n";
			}
			print "</select><br />\n";
			print "<select name='blogStatus'>\n";
			$blogStatus = getBlogStatusId ($blogId, $db);
			print "<option value='0'";
			if ($blogStatus == 0) {
				print " selected";
			}
			print ">Approved</option>\n";
			print "<option value='1'";
			if ($blogStatus == 1) {
				print " selected";
			}
			print ">Pending</option>\n";
			print "<option value='2'";
			if ($blogStatus == 2) {
				print " selected";
			}
			print ">Rejected</option>\n";
			print "<option value='3'";
			if ($blogStatus == 3) {
				print " selected";
			}
			print ">Withdrawn by owner</option>\n";
			print "<option value='4'";
			if ($blogStatus == 4) {
				print " selected";
			}
			print ">Withdrawn by indexer</option>\n";
			print "</select><br />\n";
			print "<input type=\"radio\" name=\"blogDelete\" value=\"1\" /> Delete blog.<br />";
			print "<input type=\"submit\" value=\"Submit\" /><br />\n";
			print "<input type=\"submit\" value=\"Submit\" /><br />\n";
		  print "</form>\n";
			print "</div>";
		}
		} else { # not moderator or admin
			print "You are not authorized to administrate blogs.<br />";
		}
  } else {
    print "Please log in.";
  }
}
?>