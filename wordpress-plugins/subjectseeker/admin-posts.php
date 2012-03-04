<?php
/*
Plugin Name: SubjectSeeker Administer Posts
Plugin URI: http://scienceseeker.org/
Description: Administer Posts for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssAdminPosts')) {
  class ssAdminPosts {
    function ssAdminPosts() {
      $this->version = "0.1";
    }
	
    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssAdminPosts($args) {
        extract($args);
        $options = get_option('widget_ssAdminPosts');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssAdminPosts();
        echo $after_widget;
      }
      function widget_ssAdminPosts_control() {
        $options = get_option('widget_ssAdminPosts');
        if ( $_POST['ssAdminPosts-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssAdminPosts-title']));
          update_option('widget_ssAdminPosts', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssAdminPosts-title">Title:<input class="widefat" name="ssAdminPosts-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssAdminPosts-submit" name="ssAdminPosts-submit" value="1" />';
      }
      register_sidebar_widget('ssAdminPosts', 'widget_ssAdminPosts');
      register_widget_control('ssAdminPosts', 'widget_ssAdminPosts_control');
    }
  }
}

$ssAdminPosts = new ssAdminPosts();
add_action( 'plugins_loaded', array(&$ssAdminPosts, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssAdminPosts, 'setupActivation' ));

function get_ssAdminPosts($settings = array()) {
  global $ssAdminPosts;
  AdminPosts();
}

function AdminPosts() {
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
		if ($userPriv > 1) { // admin
				$arrange = $_REQUEST["arrange"];
				$order = $_REQUEST["order"];
				$pagesize = $_REQUEST["n"];
				$offset = $_REQUEST["offset"];
				if ($arrange == null) {
					$arrange = "BLOG_POST_ID";
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
				print "<div class=\"filter-button\">Display Options</div>
				<div class=\"ss-slide-wrapper\">
				<div class=\"ss-div-2\" id=\"filter-panel\">
				<form method=\"GET\">";
				print "<input type=\"hidden\" name=\"filters\" value=\"filters\" />";
				print "Sort by: ";
				print "<select name='arrange'>\n";
				print "<option value='BLOG_POST_ID'";
				if ($arrange == "BLOG_POST_ID") {
					print " selected";
				}
				print ">ID</option>\n";
				print "<option value='BLOG_POST_STATUS_ID'";
				if ($arrange == "BLOG_POST_STATUS_ID") {
					print " selected";
				}
				print ">Status</option>\n";
				print "<option value='BLOG_POST_TITLE'";
				if ($arrange == "BLOG_POST_TITLE") {
					print " selected";
				}
				print ">Name</option>\n";
				print "<option value='BLOG_POST_URI'";
				if ($arrange == "BLOG_POST_URI") {
					print " selected";
				}
				print ">URI</option>\n";
				print "<option value='BLOG_POST_DATE_TIME'";
				if ($arrange == "BLOG_POST_DATE_TIME") {
					print " selected";
				}
				print ">Post Date</option>\n";
				print "<option value='BLOG_POST_INGEST_DATE_TIME'";
				if ($arrange == "BLOG_POST_INGEST_DATE_TIME") {
					print " selected";
				}
				print ">Added Date</option>\n";
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
				</div>
				</div>
				<br />";
				
			if ($step != NULL) {
				$postId = stripslashes($_REQUEST["postId"]);
				$postUrl = stripslashes($_REQUEST["url"]);
				$postTitle = stripslashes($_REQUEST["title"]);
				$postSummary = stripslashes($_REQUEST["summary"]);
				$postStatus = stripslashes($_REQUEST["status"]);
				$check = $_REQUEST["checkCitations"];
				$result = checkPostData($postId, $postTitle, $postSummary, $postUrl, $userId, $displayname, $db);
				if ($step == 'confirmed' || ($result == NULL && $step == 'edit')) {
					if ($check == 1) {
						removeCitations($postId, NULL, $db);
						$results = checkCitations ($postUrl, $postId, $db);
						if (is_array($results) == TRUE) {
							print "<div class=\"ss-div-2\"><span class=\"green-circle\"></span> We found the following citation(s) on $blogName: <a href=\"$postUri\">$postTitle</a></div>";
							foreach ($results as $citation) {
								storeCitation ($citation, $postId, $db);
								// Display citation
								print "<p>$citation</p>";
							}
						}
						elseif ($results == NULL) {
							print "<div class=\"ss-div-2\"><span class=\"red-circle\"></span> No citations found on $blogName: <a href=\"$postUri\">$postTitle</a></div>";
						}
						else {
							print "<div class=\"ss-div-2\"><span class=\"red-circle\"></span> ERROR: $results</div>";
						}
						print "<hr />";
					}
					
					editPost ($postId, $postTitle, $postUrl, $postSummary, $postStatus, $userId, $displayName, $db);
					print "<div class=\"ss-div-2\"><span class=\"green-circle\"></span> $postTitle (ID $postId) was updated.</div>";
					
				}
				if ($result != NULL && $step == 'edit') {
					global $adminPosts;
					print "<div class=\"ss-div-2\">$postTitle (ID $postId): <ul class=\"ss-error\">$result</ul></div>
					<form class=\"ss-div\" method=\"POST\">
					<input type=\"hidden\" name=\"step\" value=\"confirmed\" />
					<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
					<input type=\"hidden\" name=\"title\" value=\"$postTitle\" />
					<input type=\"hidden\" name=\"url\" value=\"$postUrl\" />
					<input type=\"hidden\" name=\"summary\" value=\"$postSummary\" />
					<input type=\"hidden\" name=\"recommended\" value=\"$recommended\" />
					<input type=\"hidden\" name=\"status\" value=\"$postStatus\" />
					<input type=\"hidden\" name=\"image\" value=\"$image\" />
					<p>There has been an error, are you sure you want to apply these changes?</p>
					<input class=\"ss-button\" name=\"confirm\" type=\"submit\" value=\"Yes\" /> <a class=\"ss-button\" href=\"$adminPosts\" />No</a>
					</form>";
				}
			}
			$baseUrl = removeParams();
			$postList = getPosts ($arrange, $order, $pagesize, $offset, $db);
			if ($postList == null) {
				print "<p>There are no more blogs in the system.</p>";
			}
			else {
				print "<hr />";
				foreach ($postList as $post) {
					$postId = $post["postId"];
					$postTitle = $post["title"];
					$postSummary = $post["content"];
					$postAuthorId = $post["authorId"];
					$postDate = $post["postDate"];
					$addedDate = $post["addedDate"];
					$postLanguage = $post["language"];
					$postUrl = $post["uri"];
					$postStatusId = $post["status"];
					$blogId = $post["blogId"];
					$postStatus = ucwords(blogPostStatusIdToName ($postStatusId, $db));
					$blogName = getBlogName($blogId, $db);
					print "<div class=\"ss-entry-wrapper\">
					<div class=\"post-header\">
					$postId | <a href=\"$postUrl\" target=\"_blank\">$postTitle</a> | $blogName | $postStatus
					</div>
					<div class=\"ss-div-button\">
          <div class=\"arrow-down\" title=\"Show Info\"></div>
        	</div>
					<div class=\"ss-slide-wrapper\">
					<br />
					<form method=\"POST\" enctype=\"multipart/form-data\">
					<input type=\"hidden\" name=\"step\" value=\"edit\" />";
					if ($errormsg !== null) {
						print "<p><font color='red'>Error: $errormsg</font></p>\n";
					}
					print "<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
					<p>Blog Name: $blogName</p>
					<p>Post Time: $postDate</p>
					<p>Added Time: $addedDate</p>
					<p>Title: <input type=\"text\" name=\"title\" size=\"40\" value=\"$postTitle\"/></p>
					<p><a href=\"$postUrl\" target=\"_blank\">URL:</a> <input type=\"text\" name=\"url\" size=\"40\" value=\"$postUrl\" /></p>
					<p>Summary:<br />
					<textarea name=\"summary\" rows=\"5\" cols=\"55\">$postSummary</textarea></p>";
					print "<p>Status: <select name='status'>";
					$statusList = getBlogPostStatusList ($db);
					while ($row = mysql_fetch_array($statusList)) {
						print "<option value='" . $row["BLOG_POST_STATUS_ID"] . "'";
						if ($row["BLOG_POST_STATUS_ID"] == $postStatusId) {
							print " selected";
						}
						print ">" . ucwords($row["BLOG_POST_STATUS_DESCRIPTION"]) . "</option>";
					}
					print "</select></p>
					<p><input type=\"checkbox\" class=\"checkbox\" name=\"checkCitations\" value=\"1\" /> Check for citations.</p>
					<input class=\"ss-button\" type=\"submit\" value=\"Submit\" />
					<br />
					</form>
					</div>
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
			print "You are not authorized to administrate posts.<br />";
		}
  } else { // not logged in
    print "Please log in.";
  }
}
?>