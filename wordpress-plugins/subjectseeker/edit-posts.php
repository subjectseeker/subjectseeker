<?php
/*
Plugin Name: SubjectSeeker Edit Posts
Plugin URI: http://scienceseeker.org/
Description: Edit posts for SubjectSeeker tool
Author: Liminality
Version: 1
Author URI: http://www.scienceseeker.org
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssEditPosts')) {
  class ssEditPosts {
    function ssEditPosts() {
      $this->version = "0.1";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssEditPosts($args) {
        extract($args);
        $options = get_option('widget_ssEditPosts');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssEditPosts();
        echo $after_widget;
      }
      function widget_ssEditPosts_control() {
        $options = get_option('widget_ssEditPosts');
        if ( $_POST['ssEditPosts-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssEditPosts-title']));
          update_option('widget_ssEditPosts', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssEditPosts-title">Title:<input class="widefat" name="ssEditPosts-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssEditPosts-submit" name="ssEditPosts-submit" value="1" />';
      }
      register_sidebar_widget('ssEditPosts', 'widget_ssEditPosts');
      register_widget_control('ssEditPosts', 'widget_ssEditPosts_control');
    }
  }
}

$ssEditPosts = new ssEditPosts();
add_action( 'plugins_loaded', array(&$ssEditPosts, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssEditPosts, 'setupActivation' ));

function get_ssEditPosts($settings = array()) {
	global $ssEditPosts;
  scanPosts();
}

// Non-widget functions

function scanPosts() {
	if (is_user_logged_in()){
		// Connect to DB.
		$db  = ssDbConnect();
		$step = $_REQUEST["step"];
		global $current_user;
		global $homeUrl;
		global $userPosts;
    get_currentuserinfo();
    $displayName = $current_user->user_login;
    $email = $current_user->user_email;
		$userId = addUser($displayName, $email, $db);
		
		// Filter panel values.
		$arrange = $_REQUEST["arrange"];
		$order = $_REQUEST["order"];
		$pagesize = $_REQUEST["n"];
		$offset = $_REQUEST["offset"];
		$blog = $_REQUEST["blog"];
		if ($arrange == null) {
			$arrange = "publicationTime";
		}
		if ($order == null) {
			$order = "descending";
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
		
		if ($step == NULL) {
			print "<p>Please select the posts that you would like to scan for citations.</p>";
		}
		
		// Filters
		print "<div class=\"toggle-button\">Display Options</div>
		<div class=\"ss-slide-wrapper\">
		<div class=\"ss-div-2\" id=\"filter-panel\">
		<form method=\"GET\">
		<input type=\"hidden\" name=\"filters\" value=\"filters\" />
		Sort by:
		<select name='arrange'><option value='postId'";
		if ($arrange == "postId") {
			print " selected";
		}
		print ">ID</option>\n";
		print "<option value='postTitle'";
		if ($arrange == "postTitle") {
			print " selected";
		}
		print ">Title</option>\n";
		print "<option value='publicationTime'";
		if ($arrange == "publicationTime") {
			print " selected";
		}
		print ">Publication Time</option>\n";
		print "<option value='ingestTime'";
		if ($arrange == "ingestTime") {
			print " selected";
		}
		print ">Added Time</option>\n";
		print "</select>\n";
		print " | <select name='order'>\n";
		print "<option value='ascending'";
		if ($order == "ascending") {
			print " selected";
		}
		print ">Ascending</option>\n";
		print "<option value='descending'";
		if ($order == "descending") {
			print " selected";
		}
		print ">Descending</option>\n";
		print "</select>";
		print " | <select name='blog'>\n";
		print "<option value=''>All blogs</option>\n";
		// Get blog ids from all the blogs from the user
		$userBlogsList = getBlogIdsByUserId($userId, NULL, $db);
		// Get blog names and ids
		$blogsList = getBlogList($userBlogsList, 'blogName', 'descending', 100, 0, $db);
		foreach ($blogsList as $blogs) {
			$id = $blogs["id"];
			$name = $blogs["name"];
			print "<option value='" . $id . "'";
			if ($id == $blog) {
				print " selected";
		}
			print ">" . ucwords($name) . "</option>\n";
		}
		print "</select><br />
		Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/> | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/><br />
		<input class=\"ss-button\" type=\"submit\" value=\"Go\" />
		</form>
		</div>
		</div>
		<br />";
		
		if ($_REQUEST["addPosts"] == 1) {
			foreach ($blogsList as $blog) {
		  	$scanPosts .= crawlBlogs($blog, $db);
				$scanPosts .= "<hr />";
			}
		}
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
				print "<hr />
				$scanPosts";
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
						removeCitations($postId, NULL, $db);
						$results = checkCitations ($postUri, $postId, $db);
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
				}
				// After the results, go back to scan posts or go to the homepage
				print "<div class=\"ss-div-2\"><a href=\"$userPosts\" class=\"ss-button\">Go back to your posts</a> <a href=\"$homeUrl\" class=\"ss-button\">Homepage</a></div>";
			}
			
			if ($step == NULL) {
				// List of posts
				print "<form method=\"POST\">\n
				<input type=\"hidden\" name=\"step\" value=\"scan\" />
				<div class=\"ss-div-2\"><input type=\"checkbox\" class=\"checkall\"> Check / Uncheck All <span class=\"alignright\"><input type=\"checkbox\" name=\"addPosts\" value=\"1\" /> Scan Blogs for New Posts</span></div>
				<hr />";
				foreach ($postIds as $i => $value) {
					$blogId = $blogIds[$i];
					$postId = $postIds[$i];
					$postUri = $postUris[$i];
					$postTitle = $postTitles[$i];
					$postSummary = strip_tags($postSummaries[$i], '<br>');
					$blogName = $blogNames[$i];
					$hasCitation = $hasCitations[$i];
					$editorRecommended = getEditorsPicksStatus($postId, $db);
					$citations = postIdToCitation($postId, $db);
					if ($postTitle == NULL) {
						$postTitle = $postUri;
					}
					print "<div class=\"ss-entry-wrapper alignleft\"><input type=\"checkbox\" class=\"checkbox\" name=\"check-$postId\" value=\"1\" /> <span class=\"ss-postTitle\"><a href=\"$postUri\" target=\"_blank\">$postTitle</a></span>";
					print "<div class=\"ss-div-button\">
					<div class=\"arrow-down\"></div>
					</div>
					<div class=\"ss-slide-wrapper\">
					<div id=\"padding-content\">
					<div class=\"post-summary\">$postSummary</div>
					<br />";
					if ($citations) {
						print "<div class=\"citation-wrapper\">";
						foreach ($citations as $citation) {
							$citationId = $citation["id"];
							$citationText = utf8_decode($citation["text"]);
							print "<input type=\"hidden\" name=\"citationId[]\" value=\"$citationId\" />
							<p>$citationText</p>";
						}
						print "</div>";
					}
					print "</div>
					</div>
					<div class=\"ss-blogTitle\" style=\"width: 100%\">$blogName</div>";
					if ($hasCitation == 1 || $editorRecommended) {
						print "<div class=\"badges\" style=\"bottom: -10px;\">";
						if ($hasCitation == 1) print "<span class=\"citation-mark\"></span>";
          	if ($editorRecommended) print "<span class=\"editors-mark\"></span>";
            print "<div id=\"etiquettes\" class=\"ss-slide-wrapper\">";
              if ($hasCitation == 1) {
                print "<div class=\"citation-mark-content\" title=\"Post citing a peer-reviewed source\">
                <span>Citation</span>
                </div>";
							}
              if ($editorRecommended) {
                print "<div class=\"editors-mark-content\" title=\"Recommended by our editors\">
                <span>Editor's Pick</span>
                </div>";
							}
            print "</div>
          </div>";
					}
					print "</div>
					<hr class=\"alignleft\" style=\"width: 102.6%\" />";
				}
				print "<div class=\"ss-div\"><input class=\"ss-button\"type=\"submit\" value=\"Scan\" /></div>
				</form>
				<br style=\"clear: both;\" />";
				
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
