<?php
/*
Plugin Name: SubjectSeeker Scan for Citations
Plugin URI: http://scienceseeker.org/
Description: Scan for Citations for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.binaryparticle.com
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('csScanPosts')) {
  class csScanPosts {
    function csScanPosts() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_csScanPosts($args) {
        extract($args);
        $options = get_option('widget_csScanPosts');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_csScanPosts();
        echo $after_widget;
      }
      function widget_csScanPosts_control() {
        $options = get_option('widget_csScanPosts');
        if ( $_POST['csScanPosts-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['csScanPosts-title']));
          update_option('widget_csScanPosts', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="csScanPosts-title">Title:<input class="widefat" name="csScanPosts-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="csScanPosts-submit" name="csScanPosts-submit" value="1" />';
      }
      register_sidebar_widget('csScanPosts', 'widget_csScanPosts');
      register_widget_control('csScanPosts', 'widget_csScanPosts_control');
    }
  }
}

$csScanPosts = new csScanPosts();
add_action( 'plugins_loaded', array(&$csScanPosts, 'setupWidget') );
register_activation_hook( __FILE__, array( &$csScanPosts, 'setupActivation' ));

function get_csScanPosts($settings = array()) {
	global $csScanPosts;
  scanPosts();
}

// Non-widget functions

function scanPosts() {
	if (is_user_logged_in()){
		// Connect to DB.
		$db  = ssDbConnect();
		$step = $_REQUEST["step"];
		global $current_user;
    get_currentuserinfo();
    $displayName = $current_user->display_name;
    $email = $current_user->user_email;
		$userId = addUser($displayName, $email, $db);
		
		// Filter panel values.
		$arrange = $_REQUEST["arrange"];
		$order = $_REQUEST["order"];
		$pagesize = $_REQUEST["n"];
		$offset = $_REQUEST["offset"];
		$blog = $_REQUEST["blog"];
		if ($arrange == null) {
			$arrange = "BLOG_POST_DATE_TIME";
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
		
		// Get blog ids of the requested blogs from the user.
		$userBlogs = getBlogIdsByUserId($userId, $blog, $db);
		if ($userBlogs == NULL) {
			return print "$displayName has no active blogs.";
		}
		
		print "<p>Please select the posts that you would like to scan for citations.</p>";
		
		// Filters
		print "<div class=\"ss-div-2\">
		<form method=\"GET\">
		<input type=\"hidden\" name=\"filters\" value=\"filters\" />
		Sort by:
		<select name='arrange'><option value='BLOG_POST_ID'";
		if ($arrange == "BLOG_POST_ID") {
			print " selected";
		}
		print ">ID</option>\n";
		print "<option value='BLOG_POST_TITLE'";
		if ($arrange == "BLOG_POST_TITLE") {
			print " selected";
		}
		print ">Title</option>\n";
		print "<option value='BLOG_POST_DATE_TIME'";
		if ($arrange == "BLOG_POST_DATE_TIME") {
			print " selected";
		}
		print ">Publication Time</option>\n";
		print "<option value='BLOG_POST_INGEST_DATE_TIME'";
		if ($arrange == "BLOG_POST_INGEST_DATE_TIME") {
			print " selected";
		}
		print ">Added Time</option>\n";
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
		print "</select>";
		print " | <select name='blog'>\n";
		print "<option value=''>All blogs</option>\n";
		// Get blog ids from all the blogs from the user
		$userBlogsList = getBlogIdsByUserId($userId, NULL, $db);
		// Get blog names and ids
		$blogsList = getBlogList($userBlogsList, 'BLOG_NAME', 'DESC', 100, 0, $db);
		foreach ($blogsList as $blogs) {
			$id = $blogs["id"];
			$name = $blogs["name"];
			print "<option value='" . $id . "'";
			if ($id == $blog) {
				print " selected";
		}
			print ">" . ucwords($name) . "</option>\n";
		}
		print "</select><br />\n";
		print "Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/>";
		print " | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/><br />";
		print "<input class=\"ss-button\" type=\"submit\" value=\"Go\" />
		</form>
		</div>";
		
		// Get blog posts data from blog ids
		$blogPostData = blogIdsToBlogPostData($userBlogs, $arrange, $order, $pagesize, $offset, $db);
		while ($row = mysql_fetch_array($blogPostData)) {
			$postIds[] = $row["BLOG_POST_ID"];
			$blogIds[] = $row["BLOG_ID"];
			$postUris[] = $row["BLOG_POST_URI"];
			$postSummaries[] = $row["BLOG_POST_SUMMARY"];
			$postTitles[] = $row["BLOG_POST_TITLE"];
			$hasCitations[] = $row["BLOG_POST_HAS_CITATION"];
		}
		if ($postIds == NULL) {
			print "<p>There are no more posts in our database.</p>";
		}
		else {
			foreach ($blogIds as $id) {
				// Get blog names from blog ids
				$blogNames[] = getBlogName($id, $db);
			}
			
			if ($step == 'scan') {
				// Results from the scan
				print "<hr class=\"ss-div-2\" />";
				foreach ($postIds as $i => $value) {
					$blogId = $blogIds[$i];
					$postId = $postIds[$i];
					$postUri = $postUris[$i];
					$postTitle = $postTitles[$i];
					$blogName = $blogNames[$i];
					//If 1, check this post
					$check = $_REQUEST["check-$postId"];
					//If 1, check the 10 most recent posts
					$scanNow = $_REQUEST["scanNow"];
					if ($check == 1 || $scanNow == 1) {
						$citations = checkCitations ($postUri);
						$citation = parseCitations ($postId, $citations, $db);
						if ($citation != NULL) {
							print "<p><span class=\"green-circle\"></span> We found the following citation(s) on $blogName: <a href=\"$postUri\">$postTitle</a></p>";
							foreach ($citation as $value) {
								storeCitation ($value, $postId, $db);
								// Display citation
								print "<p>".html_entity_decode($value)."</p>";
							}
						}
						else {
							print "<p><span class=\"red-circle\"></span> No citations found on $blogName: <a href=\"$postUri\">$postTitle</a></p>";
						}
						print "<hr class=\"ss-div-2\" />";
					}
				}
				global $homeUrl;
				global $userPosts;
				// After the results, go back to scan posts or go to the homepage
				print "<div class=\"ss-div\"><a href=\"$userPosts\" class=\"ss-button\">Go back to your posts</a> <a href=\"$homeUrl\" class=\"ss-button\">Homepage</a></div>";
			}
			
			if ($step == NULL) {
				// List of posts
				print "<form method=\"POST\">\n
				<input type=\"hidden\" name=\"step\" value=\"scan\" />
				<div class=\"ss-div-2\"><input type=\"checkbox\" class=\"checkall\"> Check / Uncheck All</div>
				<hr />";
				foreach ($postIds as $i => $value) {
					$blogId = $blogIds[$i];
					$postId = $postIds[$i];
					$postUri = $postUris[$i];
					$postTitle = $postTitles[$i];
					$postSummary = $postSummaries[$i];
					$blogName = $blogNames[$i];
					$hasCitation = $hasCitations[$i];
					if ($postTitle == NULL) {
						$postTitle = $postUri;
					}
					print "<div class=\"ss-entry-wrapper\"><input type=\"checkbox\" class=\"checkbox\" name=\"check-$postId\" value=\"1\" /> <span class=\"ss-postTitle\"><a href=\"$postUri\">$postTitle</a></span>";
					if ($hasCitation == 1) {
						print " CITATION";
					}
					print "<div class=\"ss-div-button\"><div class=\"ss-right\"><span class=\"ss-hidden-text\">Click for details </span><span class=\"arrow-up\"></span></div>
					</div>
					<div class=\"ss-slide-wrapper\"><span class=\"ss-summary\"><p class=\"ss-bold\">Summary:</p><p>$postSummary</p></span></div>
					<div class=\"ss-blogTitle\">$blogName</div>
					</div>
					<hr />";
				}
				print "<div class=\"ss-div\"><input class=\"ss-button\"type=\"submit\" value=\"Scan\" /></div>
				</form>";
				
				// Current URL without parameters
				$baseUrl = removeParams();
				// Buttons for pages
				$nextOffset = $offset + $pagesize;
				$nextParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$nextOffset";
				$nextUrl = $baseUrl . $nextParams;
				print "<div class=\"alignright\"><h4><a title=\"Next page\" href=\"$nextUrl\"><b>Next Page »</b></a></h4></div>";
			}
		}
		if ($offset > 0) {
			$previousOffset = $offset - $pagesize;
			$previousParams = "?filters=filters&arrange=$arrange&order=$order&n=$pagesize&offset=$previousOffset";
			$previousUrl = $baseUrl . $previousParams;
			print "<div class=\"alignleft\"><h4><a title=\"Previous page\" href=\"$previousUrl\"><b>« Previous Page</b></a></h4></div><br />";
		}
	}
	else { // Not logged in
		print "Please log in.";
	}
}

?>
