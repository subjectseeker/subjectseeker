<?php
/*
Plugin Name: SubjectSeeker Display Feed
Plugin URI: http://scienceseeker.org/
Description: Display feed, filtered as requested
Author: Jessica P. Hekman
Version: 0.1
Author URI: http://www.arborius.net/~jphekman/
*/

/*
 * PHP widget methods
 */

include_once "ss-includes.inc";

if (!class_exists('ssDisplayFeed')) {
  class ssDisplayFeed {
    function ssDisplayFeed() {
      $this->version = "0.1";
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
	$postsData = generateSearchQuery ($queryList, $settings, $errormsgs, $db);
	
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
			$personaId = addPersona($userId, $displayName, $db);
		}
		
		
		while ($row = mysql_fetch_array($postsData)) {
			$post = NULL;
			$postDate = date("m-d-y", strtotime($row["BLOG_POST_DATE_TIME"]));
			if (! isset($posts["$postDate"])) $posts["$postDate"] = array();
			$post["id"] = $row["BLOG_POST_ID"];
			$post["author"] = insanitize($row["BLOG_AUTHOR_ACCOUNT_NAME"]);
			$post["blog_name"] = insanitize($row[ "BLOG_NAME"]);
			$post["blog_uri"] = insanitize($row[ "BLOG_URI"]);
			$post["date"] = strtotime($row["BLOG_POST_DATE_TIME"]);
			$post["feed_uri"] = insanitize($row[ "BLOG_SYNDICATION_URI"]);
			$post["summary"] = insanitize(strip_tags($row[ "BLOG_POST_SUMMARY"], '<br>'));
			$post["title"] = insanitize($row[ "BLOG_POST_TITLE"]);
			$post["uri"] = insanitize($row[ "BLOG_POST_URI"]);
			$post["hasCitation"] = $row["BLOG_POST_HAS_CITATION"];
			array_push($posts["$postDate"], $post);
		}
		
		if ($posts == NULL) {
			print "<div id=\"padding-content\">No results found for your search parameters.</div>";
		}
		
		else {
			foreach ($posts as $day => $dayPosts) {
				$formatDay = date("F d, Y", $dayPosts[0]["date"]);
				print "<div>
				<h3>$formatDay</h3>";
				foreach ($dayPosts as $entry) {
					$postId = $entry["id"];
					$blogAuthor = $entry[ "author" ];
					$blogName = $entry[ "blog_name" ];
					$blogUri = $entry[ "blog_uri" ];
					$postDate = date("g:i A", $entry["date"]);
					$blogFeed = $entry[ "feed_uri" ];
					$postProfile = $myHost . "/post/" . $postId;
					$postSummary = html_entity_decode(strip_tags($entry["summary"], '<br>'));
					$postTitle = $entry[ "title" ];
					$postUri = $entry[ "uri" ];
					$postHasCitation = $entry[ "hasCitation" ];
					
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
					if ($personaId) $userRecStatus = getRecommendationStatus($postId, $personaId, $db);
					
					$editorsPicksStatus = getEditorsPicksStatus($postId, $db);
					
					// Get number of recommendations for this post
					$recCount = getRecommendationsCount($postId, NULL, $db);
					
					// Get number of comments for this post
					$commentCount = getRecommendationsCount($postId, "comments", $db);
					
					print "<div class=\"ss-entry-wrapper\">
					<div class=\"data-carrier\" id=\"$postProfile\" data-personaId=\"$personaId\">
					<div class=\"post-extras alignleft\">
					<div class=\"recommendation-wrapper\">";
					if ($userRecStatus) print "<div class=\"recommend\" id=\"remove\" title=\"Remove recommendation and note\" style=\"background-image: url(/images/icons/ss-sprite.png); height: 18px; background-position: center -19px; background-repeat: no-repeat;\"></div>";
					else print "<div class=\"recommend\" id=\"recommend\" title=\"Recommend\" style=\"background-image: url(/images/icons/ss-sprite.png); height: 18px; background-position: center 0px; background-repeat: no-repeat;\"></div>";
					print "$recCount</div></div>
					<div class=\"post-wrapper\">
					<div class=\"post-header\">$postDate | <span class=\"ss-postTitle\"><a href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a></span></div>
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
					if ($personaId) {
					print "<div class=\"rec-comment\">
					<div class=\"ss-div-2\">
					<div class=\"text-area\">
					<form method=\"POST\" enctype=\"multipart/form-data\">
					<span class=\"subtle-text\">Leave a note!</span><div class=\"ss-div-2\">
					<textarea class=\"textArea\" name=\"comment\" rows=\"3\" cols=\"59\"></textarea><span class=\"alignright\"><span class=\"charsLeft\">120</span> characters left.</span>
					</div>
					<input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"store\" value=\"Submit\">
					</form>
					<br />
					</div>
					<div class=\"comment-notification\"></div>";
					if ($userPriv > 0) {
						print "<div class=\"toggle-button\">Related Image</div>
										<div class=\"ss-slide-wrapper\">
											<div class=\"ss-div-2\" id=\"filter-panel\">
												<p>Please submit your comment before submiting an image.</p>
												<form method=\"POST\" action=\"/edit-image/\" enctype=\"multipart/form-data\">
													<input type=\"hidden\" name=\"postId\" value=\"$postId\" />
													<div>
														<div class=\"alignleft\">
															<h4>Maximum Size</h4>
															<span class=\"subtle-text\">1 MB</span>
														</div>
														<div class=\"alignleft\" style=\"margin-left: 40px;\">
															<h4>Minimum Width/Height</h4>
															<span class=\"subtle-text\">580px x 200px</span>
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
				}
				print "</div>";
			}
		}
	}
	if ($minimal != TRUE) {
		global $mainFeed;
		pageButtons ($mainFeed, $nextText = "Older Entries »", $prevText = "« Newer Entries");
	}
}

?>
