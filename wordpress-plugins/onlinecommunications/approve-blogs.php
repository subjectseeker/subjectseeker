<?php
/*
Plugin Name: OnlineCommunications Approve Blogs
Plugin URI: http://scienceseeker.org/
Description: Approve blogs for OnlineCommunications tool
Author: Jessica P. Hekman
Version: 1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "oc-includes.inc";

if (!class_exists('ocApproveBlogs')) {
  class ocApproveBlogs {
    function ocApproveBlogs() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ocApproveBlogs($args) {
        extract($args);
        $options = get_option('widget_ocApproveBlogs');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ocApproveBlogs();
        echo $after_widget;
      }
      function widget_ocApproveBlogs_control() {
        $options = get_option('widget_ocApproveBlogs');
        if ( $_POST['ocApproveBlogs-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ocApproveBlogs-title']));
          update_option('widget_ocApproveBlogs', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ocApproveBlogs-title">Title:<input class="widefat" name="ocApproveBlogs-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ocApproveBlogs-submit" name="ocApproveBlogs-submit" value="1" />';
      }
      register_sidebar_widget('ocApproveBlogs', 'widget_ocApproveBlogs');
      register_widget_control('ocApproveBlogs', 'widget_ocApproveBlogs_control');
    }
  }
}

$ocApproveBlogs = new ocApproveBlogs();
add_action( 'plugins_loaded', array(&$ocApproveBlogs, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ocApproveBlogs, 'setupActivation' ));

function get_ocApproveBlogs($settings = array()) {
  global $ocApproveBlogs;
  doApproveBlogs();
}

function doApproveBlogs() {
  $step = $_REQUEST["step"];
  $db = ocDbConnect();

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
		
        print "<form method=\"POST\">\n";
        print "<input type=\"hidden\" name=\"step\" value=\"approve\" />";
        $blogList = getPendingBlogs($db);
        foreach ($blogList as $blog) {
            $blogId = $blog["id"];
            $blogName = $blog["name"];
            $blogUri = $blog["uri"];
            $blogDescription = $blog["blogdescription"];
            $blogSyndicationUri = $blog["syndicationuri"];
            $blogtopics = getBlogTopics($blogId, $db);
            //$topic1 = $_REQUEST["topic1"];
            //$topic2 = $_REQUEST["topic2"];
            print "<input type=\"hidden\" name=\"blogId[]\" value=\"$blogId\" />\n";
            if ($errormsg !== null) {
                print "<p><font color='red'>Error: $errormsg</font></p>\n";
            }
			print "<p><strong>$blogName</strong></p>";
            print "<p>*Required field</p>\n<p>\n";
            print "*Blog name: <input type=\"text\" name=\"blogname[]\" size=\"40\" value=\"$blogName\"/>\n</p>\n<p>\n*Blog URL: <input type=\"text\" name=\"blogurl[]\" size=\"40\" value=\"$blogUri\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)";
            print "</p><p>*Blog syndication URL: <input type=\"text\" name=\"blogsyndicationuri[]\" size=\"40\" value=\"$blogSyndicationUri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)";
            print "</p><p>Blog description:<br /><textarea name=\"blogdescription[]\" rows=\"5\" cols=\"70\">$blogDescription</textarea><br />\n";
            print "Blog topics: <select name='topic1[]'>\n";  
            print "<option value='-1'>None</option>\n";
            $topicList = getTopicList(true, $db);
            while ($row = mysql_fetch_array($topicList)) {
                print "<option value='" . $row["TOPIC_ID"] . "'";
                if ($row["TOPIC_ID"] == $blogtopics[0]) {
                    print " selected";
                    }
                    print ">" . $row["TOPIC_NAME"] . "</option>\n";
                    }
            print "</select>&nbsp;<select name='topic2[]'>\n";
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
            print "<input type=\"radio\" name=\"$blogId-blog\" value=\"1\" /> Approve<br />";
            print "<input type=\"radio\" name=\"$blogId-blog\" value=\"2\" /> Reject<br />";  
            }
            print "<input type=\"submit\" value=\"Submit\" />\n";
            print "</form>\n";
			
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
	  
	  editBlog ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayname, $db);
	  $result = editBlog($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayname, $db);
	  $oldBlogName = getBlogName($blogId, $db);
	  
	  if ($result == NULL) {
		  if ($status == 1) {
			   approveBlog($blogId, $db);
			   print "<p>Blog $blogname (id $blogId) APPROVED</p>\n";
			   } 
		  if ($status == 2) {
				rejectBlog($blogId, $db);
				$contacts = getBlogContacts($blogId, $db);
				print "<p>Blog $oldBlogName (id $blogId) REJECTED (email contact(s):";
				foreach ($contacts as $contact) {
					print " <a href=\"mailto:$contact\">$contact</a>";
					}
					print ")</p>\n";
					}
				print "<p>$blogname (id $blogId) was updated.</p>\n";  
			} else {
				print "<p><font color='red'>$oldBlogName (id $blogId): $result</font></p>\n";
				}
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
