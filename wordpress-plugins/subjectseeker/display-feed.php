<?php
/*
Plugin Name: SubjectSeeker Display Feed
Plugin URI: http://scienceseeker.org/
Description: Display feed, filtered as requested
Author: Jessica P. Hekman
Version: 1.0
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssDisplayFeed')) {
  class ssDisplayFeed {
    function ssDisplayFeed() {
      $this->version = "1.0";
    }

    function setupActivation() {
      function get_and_delete_option($setting) { $v = get_option($setting); delete_option($setting); return $v; }
    }

    function setupWidget() {
      if (!function_exists('register_sidebar_widget')) return;
      function widget_ssDisplayFeed($args) {
        extract($args);
        $options = get_option('widget_ssDisplayFeed');
        $title = $options['title'];
        echo $before_widget . $before_title . $title . $after_title;
        get_ssDisplayFeed();
        echo $after_widget;
      }
      function widget_ssDisplayFeed_control() {
        $options = get_option('widget_ssDisplayFeed');
        if ( $_POST['ssDisplayFeed-submit'] ) {
          $options['title'] = strip_tags(stripslashes($_POST['ssDisplayFeed-title']));
          update_option('widget_ssDisplayFeed', $options);
        }
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        echo
          '<p><label for="ssDisplayFeed-title">Title:<input class="widefat" name="ssDisplayFeed-title" type="text" value="'.$title.'" /></label></p>'.
          '<input type="hidden" id="ssDisplayFeed-submit" name="ssDisplayFeed-submit" value="1" />';
      }
      register_sidebar_widget('ssDisplayFeed', 'widget_ssDisplayFeed');
      register_widget_control('ssDisplayFeed', 'widget_ssDisplayFeed_control');
    }
  }
}

$ssDisplayFeed = new ssDisplayFeed();
add_action( 'plugins_loaded', array(&$ssDisplayFeed, 'setupWidget') );
register_activation_hook( __FILE__, array( &$ssDisplayFeed, 'setupActivation' ));

function get_ssDisplayFeed($query = NULL, $minimal = FALSE) {
	global $ssDisplayFeed;
	displayFeed($query, $minimal);
}

/*
 * Non-plugin functions
 */

function displayFeed($query, $minimal) {
	
	$db = ssDbConnect();
	if ($query) parse_str($query, $parsedQuery);
	$queryList = httpParamsToSearchQuery($parsedQuery);
	$settings = httpParamsToExtraQuery($parsedQuery);
	$errormsgs = array();
	$settings["type"] = "post";
	$postsData = generateSearchQuery ($queryList, $settings, 0, $errormsgs, $db);
	
	if (! empty($errormsgs)) {
		print "<div id=\"padding-content\">";
		foreach ($errormsgs as $error) {
			print "<p>Error: $error</p>";
		}
		print "</div>";
	}
	
	else {
		global $mainFeed;
		if (is_user_logged_in()){
			global $current_user;
			$db = ssDbConnect();
			get_currentuserinfo();
			$displayName = $current_user->user_login;
			$email = $current_user->user_email;
			$userId = addUser($displayName, $email, $db);
			$userPriv = getUserPrivilegeStatus($userId, $db);
			$twitterStatus = getUserSocialAccount(1, $userId, $db);
		}
		
		// Get current URL for Social Network sync page.
		$currentUrl = getURL();
		
		if ($postsData) {
			while ($row = mysql_fetch_array($postsData)) {
				$postId = $row["BLOG_POST_ID"];
				$blogName = $row[ "BLOG_NAME"];
				$blogUri = $row[ "BLOG_URI"];
				$postDate = strtotime($row["BLOG_POST_DATE_TIME"]);
				$formatHour = date("g:i A", $postDate);
				$postSummary = strip_tags($row[ "BLOG_POST_SUMMARY"]);
				$postTitle = $row[ "BLOG_POST_TITLE"];
				$postUri = $row[ "BLOG_POST_URI"];
				$postProfile = $myHost . "/post/" . $postId;
				$postHasCitation = $row["BLOG_POST_HAS_CITATION"];
				$formatDay = date("F d, Y", $postDate);
				
				// Check if this post should be grouped with other posts of the same day.
				if ($previousDay != $formatDay && !$minimal) {
					print "<div class=\"ss-div-2\">
					<h3>$formatDay</h3>";
				}
				
				// If post doesn't have a title, use the url instead.
				if (! $postTitle) $postTitle = $postUri;
				
				// The blog category may not be present.
				$blogCatSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, PRIMARY_BLOG_TOPIC AS BT, BLOG_POST AS P WHERE P.BLOG_POST_ID = $postId AND BT.BLOG_ID = P.BLOG_ID AND T.TOPIC_ID = BT.TOPIC_ID;";
				$result = mysql_query( $blogCatSQL, $db);
				
				$categories = array();
				while ( $row = mysql_fetch_array( $result ) ) {
					array_push($categories, $row["TOPIC_NAME"]);
				}
				
				// Get citations
				if ($postHasCitation) $postCitations = postIdToCitation($postId, $db);
				
				// Check if user has recommended this post
				if ($userId) $userRecStatus = getRecommendationsCount($postId, NULL, $userId, NULL, $db);
				
				$editorsPicksStatus = getRecommendationsCount($postId, NULL, NULL, 1, $db);
				
				// Get number of recommendations for this post
				$recCount = getRecommendationsCount($postId, NULL, NULL, NULL, $db);
				
				// Get number of comments for this post
				$commentCount = getRecommendationsCount($postId, "comments", NULL, NULL, $db);
				
				print "<div class=\"ss-entry-wrapper\">
				<div class=\"data-carrier\" id=\"$postId\" data-user=\"$userId\">
				<div class=\"post-extras alignleft\">
				<div class=\"recommendation-wrapper\">";
				if ($userRecStatus) print "<div class=\"red-star\" id=\"remove\" title=\"Remove recommendation and note\"></div>";
				else print "<div class=\"grey-star\" id=\"recommend\" title=\"Recommend\"></div>";
				print "$recCount</div></div>
				<div class=\"post-wrapper\">
				<div class=\"post-header\">$formatHour | <a class=\"red-title ss-postTitle\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a></div>
				<div class=\"ss-div-button\"><div class=\"arrow-down\" title=\"Show Extra Info\"></div></div>
				<div id=\"post-info\" class=\"ss-slide-wrapper\" style=\"display: none; \">
				<div id=\"padding-content\"><div title=\"Summary\">$postSummary</div>";
				// Add citations to summary if available
				if ($postHasCitation) {
					print "<div class=\"citation-wrapper\"><br />";
					foreach ($postCitations as $citation) {
						print "<p>".$citation["text"]."</p>";
					}
					print '</div>';
				}
				print "</div>
				<div class=\"comments-list-wrapper\"></div>";
				if ($userId) {
					print "<div class=\"rec-comment\">
					<div class=\"ss-div-2\">
					<div class=\"text-area\">
					<form method=\"POST\" enctype=\"multipart/form-data\">
					<span class=\"subtle-text\">Leave a note!<span class=\"alignright\"><span style=\"color: #383838;\" class=\"charsLeft\">104</span> characters left.</span></span>
					<div class=\"ss-div-2\">
					<textarea class=\"textArea\" name=\"comment\" rows=\"3\" cols=\"59\"></textarea>
					</div>
					<input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"store\" value=\"Submit\" />";
					if ($twitterStatus) {
						print " <span class=\"subtle-text alignright\" title=\"The blog's twitter handle and post's url will be included in your tweet.\"><input class=\"tweet-note\" type=\"checkbox\" value=\"true\" /> Tweet this note.</span>";
					}
					else {
						print " <a class=\"alignright subtle-text\" href=\"/sync/twitter/?url=$currentUrl\">Sync with Twitter</a>";
					}
					print "</form>
					<br />
					</div>
					<div class=\"comment-notification\"></div>";
					if ($userPriv > 0) {
						print "<div class=\"toggle-button\">Related Image</div>
										<div id=\"padding-content\" class=\"ss-slide-wrapper\">
											<div id=\"filter-panel\">
												<form method=\"POST\" action=\"/edit-image/\" enctype=\"multipart/form-data\">
													<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
													<div>
														<div class=\"alignleft\">
															<h4>Maximum Size</h4>
															<span class=\"subtle-text\">1 MB</span>
														</div>
														<div class=\"alignleft\" style=\"margin-left: 40px;\">
															<h4>Minimum Width/Height</h4>
															<span class=\"subtle-text\">580px / 200px</span>
														</div>
													</div>
													<br style=\"clear: both;\" />
													<div class=\"ss-div-2\"><input type=\"file\" name=\"image\" /> <input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></div>
												</form>
											</div>
										</div>";
					}
					print "</div>
					</div>";
				}
				print "</div>
				<div class=\"info-post\">
				<span class=\"ss-blogTitle\"><a href=\"$blogUri\" target=\"_blank\" title=\"Permanent link to $blogName homepage\" rel=\"alternate\">$blogName</a></span><span class=\"alignright\"><span class=\"the_tags\">";
				foreach ($categories as $i => $category) {
					if ($i != 0) print " | ";
					print "<a href=\"$mainFeed/?type=post&filter0=blog&modifier0=topic&value0=".urlencode($category)."\" title=\"View all posts in $category\">$category</a>";
				}
				print "</span> - <span class=\"comment-button\" data-number=\"$commentCount\">$commentCount Note";
				if ($commentCount != 1) print "s";
				print "</span>
				</div>
				</div>";
				if ($postHasCitation || $editorsPicksStatus) {
					print "<div class=\"badges\">";
						if ($postHasCitation) print "<span class=\"citation-mark\"></span>";
						if ($editorsPicksStatus) print "<span class=\"editors-mark\"></span>";
						print "<div id=\"etiquettes\" class=\"ss-slide-wrapper\">";
							if ($postHasCitation) {
								print "<div class=\"citation-mark-content\" title=\"Post citing a peer-reviewed source\">
								<span>Citation</span>
								</div>";
							}
							if ($editorsPicksStatus) {
								print "<div class=\"editors-mark-content\" title=\"Recommended by our editors\">
								<span>Editor's Pick</span>
								</div>";
							}
						print "</div>
					</div>";
				}
				print "</div>
				</div>";
				if ($previousDay != $formatDay && !$minimal) {
					print "</div>";
				}
				
				$previousDay = $formatDay;
			}
		}
		else {
			print "<div id=\"padding-content\">No results found for your search parameters.</div>";
		}
	}
	if ($minimal != TRUE) {
		global $mainFeed;
		pageButtons ($mainFeed, $nextText = "Older Entries »", $prevText = "« Newer Entries");
	}
}

?>
