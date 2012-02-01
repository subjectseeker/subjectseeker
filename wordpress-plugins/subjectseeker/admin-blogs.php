<?php
/*
Plugin Name: SubjectSeeker Administer Blogs
Plugin URI: http://scienceseeker.org/
Description: Administer Blogs for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
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
  AdminBlogs();
}

function AdminBlogs() {
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
				$pagesize = $_REQUEST["n"];
				$offset = $_REQUEST["offset"];
				if ($arrange == null) {
					$arrange = "ADDED_DATE_TIME";
				}
				if ($order == null) {
					$order = "DESC";
				}
				if ($pagesize == null || is_numeric($pagesize) == FALSE) {
					$pagesize = "30";
				}
				if ($offset == null || is_numeric($offset) == FALSE) {
					$offset = "0";
				}
				print "<div class=\"ss-div-2\">
				<form method=\"GET\">";
				print "<input type=\"hidden\" name=\"filters\" value=\"filters\" />";
				print "Sort by: ";
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
				print " | <select name='order'>\n";
				print "<option value='ASC'";
				if ($order == "ASC") {
					print " selected";
				}
				print ">Ascending</option>\n";
				print "<option value='DESC'";
				if ($order == "DESC") {
					print " selected";
				}
				print ">Descending</option>\n";
				print "</select><br />\n";
				print "Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/>";
				print " | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/>";
				print "<br /><input class=\"ss-button\" type=\"submit\" value=\"Go\" />";
				print "</form>
				</div>";
				
			if ($step != NULL) {
				$blogId = stripslashes($_REQUEST["blogId"]);
				$blogname = stripslashes($_REQUEST["blogname"]);
				$blogurl = stripslashes($_REQUEST["blogurl"]);
				$blogsyndicationuri = stripslashes($_REQUEST["blogsyndicationuri"]);
				$blogdescription = stripslashes($_REQUEST["blogdescription"]);
				$topic1 = stripslashes($_REQUEST["topic1"]);
				$topic2 = stripslashes($_REQUEST["topic2"]);
				$blogStatus = stripslashes($_REQUEST["blogstatus"]);
				$oldBlogName = getBlogName($blogId, $db);
				$result = checkBlogData($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $userId, $displayname, $db);
				if ($step == 'edit') {
					if ($result == NULL) {				
						editBlogStatus ($blogId, $blogStatus, $db);
						editBlog ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $db);
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
					if ($result != NULL) {
						print "<p>$oldBlogName (id $blogId): <ul class=\"ss-error\">$result</ul></p>";
						print "<form class=\"ss-div\" method=\"POST\">
						<input type=\"hidden\" name=\"step\" value=\"confirm\" />
						<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />
						<input type=\"hidden\" name=\"blogname\" value=\"$blogname\" />
						<input type=\"hidden\" name=\"blogurl\" value=\"$blogurl\" />
						<input type=\"hidden\" name=\"blogsyndicationuri\" value=\"$blogsyndicationuri\" />
						<input type=\"hidden\" name=\"blogdescription\" value=\"$blogdescription\" />
						<input type=\"hidden\" name=\"topic1\" value=\"$topic1\" />
						<input type=\"hidden\" name=\"topic2\" value=\"$topic2\" />
						<input type=\"hidden\" name=\"blogstatus\" value=\"$blogStatus\" />
						<p>There has been an error, are you sure you want to apply these changes?</p>
						<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Yes\" /> <input class=\"ss-button\" type=\"Submit\" value=\"No\" />
						</form>";
					}
				}
				if ($step == 'confirm') {
					$confirm = $_REQUEST["confirm"];
					if ($confirm == 'Yes') {
						editBlog ($blogId, $blogname, $blogurl, $blogsyndicationuri, $blogdescription, $topic1, $topic2, $db);
						editBlogStatus ($blogId, $blogStatus, $db);	
						print "<p>$blogname (id $blogId) was updated.</p>";
					}
					else {
						$oldBlogName = getBlogName($blogId, $db);
						print "<p>$oldBlogName (id $blogId) was not updated.</p>";
					}
				}
			}
			$baseUrl = removeParams();
			$blogList = getBlogList(NULL, $arrange, $order, $pagesize, $offset, $db);
			if ($blogList == null) {
				print "<p>There are no more blogs in the system.</p>";
			}
			else {
				print "<hr />";
				foreach ($blogList as $blog) {
					$blogId = $blog["id"];
					$blogName = $blog["name"];
					$blogUri = $blog["uri"];
					$blogDescription = $blog["blogdescription"];
					$blogSyndicationUri = $blog["syndicationuri"];
					$blogAddedTime = $blog["addedtime"];
					$blogCrawledTime = $blog["crawledtime"];
					$blogStatusId = $blog["status"];
					$blogtopics = getBlogTopics($blogId, $db);
					$blogStatus = ucwords(blogStatusIdToName ($blogStatusId, $db));
					//$topic1 = $_REQUEST["topic1"];
					//$topic2 = $_REQUEST["topic2"];
					print "<div class=\"ss-entry-wrapper\">
					$blogId | <a href=\"$blogUri\" target=\"_blank\">$blogName</a> | $blogStatus | $blogAddedTime
					<div class=\"ss-div-button\">
          <div class=\"arrow-up\" title=\"Show Summary\"></div>
       		</div>
					<div class=\"ss-slide-wrapper\">
					<form method=\"POST\">
					<input type=\"hidden\" name=\"step\" value=\"edit\" />";
					if ($errormsg !== null) {
						print "<p><font color='red'>Error: $errormsg</font></p>\n";
					}
					print "<input type=\"hidden\" name=\"blogId\" value=\"$blogId\" />\n";
					print "<p>Added: $blogAddedTime</p>"; 
					print "<p>Crawled: $blogCrawledTime</p>";
					print "<p>*Required field</p>\n";
					print "<p>*Blog name: <input type=\"text\" name=\"blogname\" size=\"40\" value=\"$blogName\"/></p>\n";
					print "<p>*<a href=\"$blogUri\" target=\"_blank\">Blog URL:</a> <input type=\"text\" name=\"blogurl\" size=\"40\" value=\"$blogUri\" /><br />(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>";
					print "<p>*<a href=\"$blogSyndicationUri\" target=\"_blank\">Blog syndication URL:</a> <input type=\"text\" name=\"blogsyndicationuri\" size=\"40\" value=\"$blogSyndicationUri\" /> <br />(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</p>";
					print "<p>Blog description:<br /><textarea name=\"blogdescription\" rows=\"5\" cols=\"55\">$blogDescription</textarea></p>\n";
					print "<p>*Blog topics: <select name='topic1'>\n";
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
					print "</select></p>\n";
					print "<p>Blog Status: <select name='blogstatus'>\n";
					$statusList = getBlogStatusList ($db);
					while ($row = mysql_fetch_array($statusList)) {
						print "<option value='" . $row["BLOG_STATUS_ID"] . "'";
						if ($row["BLOG_STATUS_ID"] == $blogStatusId) {
							print " selected";
									}
						print ">" . ucwords($row["BLOG_STATUS_DESCRIPTION"]) . "</option>\n";
					}
					print "</select></p>\n";
					print "<input class=\"ss-button\" type=\"submit\" value=\"Submit\" /><br />\n";
					print "</form>\n";
					print "</div>
					</div>
					<hr />";
				}
				// Buttons for pages
				print "<br \>";
				$nextOffset = $offset + $pagesize;
				$nextParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$nextOffset";
				$nextUrl = $baseUrl . $nextParams;
				print "<div class=\"alignright\"><h4><a title=\"Next page\" href=\"$nextUrl\"><b>Next Page »</b></a></h4></div>";
			}
			if ($offset > 0) {
				$previousOffset = $offset - $pagesize;
				$previousParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$previousOffset";
				$previousUrl = $baseUrl . $previousParams;
				print "<div class=\"alignleft\"><h4><a title=\"Previous page\" href=\"$previousUrl\"><b>« Previous Page</b></a></h4></div><br />";
			}
		} else { // not moderator or admin
			print "You are not authorized to administrate blogs.<br />";
		}
  } else { // not logged in
    print "Please log in.";
  }
}
?>