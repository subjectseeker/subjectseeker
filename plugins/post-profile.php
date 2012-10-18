<?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayPostProfile() {
	global $pages;
	if (!empty($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
  $db = ssDbConnect();
  if (isLoggedIn()){
    $authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
    $userPriv = getUserPrivilegeStatus($authUserId, $db);
		$twitterStatus = getUserSocialAccount(1, $authUserId, $db);
	}
	preg_match('/(?<=\/post\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$postId = $matchResult[0];
	
	// Use Search API to find Blog ID and Post URL
	$queryList = httpParamsToSearchQuery("type=post&filter0=identifier&value0=$postId");
	$settings = httpParamsToExtraQuery("type=post&filter0=identifier&value0=$postId");
	$postData = generateSearchQuery ($queryList, $settings, 0, $db);
	$row = mysql_fetch_array($postData["result"]);
  $postId = $row["BLOG_POST_ID"];
	$blogName = $row["BLOG_NAME"];
	$blogUri = $row["BLOG_URI"];
	$postDate = strtotime($row["BLOG_POST_DATE_TIME"]);
	$postSummary = strip_tags($row["BLOG_POST_SUMMARY"]);
	$postTitle = $row["BLOG_POST_TITLE"];
	$postUri = $row["BLOG_POST_URI"];
	$postProfile = "/post/" . $postId;
	$postHasCitation = $row["BLOG_POST_HAS_CITATION"];
	$date = date("g:i A | F d, Y", $postDate);
	$postAuthor = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
	$postLanguage = postIdToLanguageName ($postId, $db);
	
	// If post doesn't have a title, use the url instead.
	if (! $postTitle) $postTitle = $postUri;
	
	// Get blog topics.
	$blogCatSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, PRIMARY_BLOG_TOPIC AS BT, BLOG_POST AS P WHERE P.BLOG_POST_ID = $postId AND BT.BLOG_ID = P.BLOG_ID AND T.TOPIC_ID = BT.TOPIC_ID;";
	$result = mysql_query( $blogCatSQL, $db);
	
	$blogTopics = array();
	while ( $row = mysql_fetch_array( $result ) ) {
		array_push($blogTopics, $row["TOPIC_NAME"]);
	}
	
	// Get post topics.
	$postCatSQL = "SELECT T.TOPIC_NAME FROM TOPIC AS T, POST_TOPIC AS PT WHERE PT.BLOG_POST_ID = $postId AND T.TOPIC_ID = PT.TOPIC_ID;";
	$result = mysql_query( $postCatSQL, $db);
	
	$postTopics = array();
	while ( $row = mysql_fetch_array( $result ) ) {
		array_push($postTopics, $row["TOPIC_NAME"]);
	}
	
	// Get citations
	if (!empty($postHasCitation)) $postCitations = postIdToCitation($postId, $db);
	
	// Check if user has recommended this post
	if (!empty($authUserId)) $recStatus = getRecommendationsCount($postId, NULL, $authUserId, NULL, $db);
	
	$editorsPicksStatus = getRecommendationsCount($postId, NULL, NULL, 1, $db);
	
	// Get number of recommendations for this post
	$recCount = getRecommendationsCount($postId, NULL, NULL, NULL, $db);
	
	// Get number of comments for this post
	$commentCount = getRecommendationsCount($postId, "comments", NULL, NULL, $db);
	
	print "<div class=\"data-carrier\" data-id=\"post-$postId\">
	<div class=\"page-title\"><a href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a></div>
	<div class=\"floater-wrapper\">
	<div id=\"post-sidebar\">
	<p><span class=\"ss-bold\">Source:</span> <a href=\"$blogUri\" target=\"_blank\" title=\"Permanent link to $blogName homepage\" rel=\"alternate\">$blogName</a></p>";
	if (!empty($postAuthor)) {
		print "<p><span class=\"ss-bold\">Author:</span> $postAuthor</p>";
	}
	print "<p><span class=\"ss-bold\">Date:</span> $date</p>";
	if (!empty($postLanguage)) {
		print "<p><span class=\"ss-bold\">Language:</span> $postLanguage</p>";
	}
	print "<p><span class=\"ss-bold\">Categories: </span>";
	foreach ($blogTopics as $i => $blogTopic) {
		if ($i != 0) print ", ";
		print "<a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=blog&amp;modifier0=topic&amp;value0=".urlencode($blogTopic)."\" title=\"View all posts in $blogTopic\">$blogTopic</a>";
	}
	print "</p>
	
	<div class=\"toggle-button\">Link to this page</div>
	<div class=\"ss-slide-wrapper\" style=\"text-align: center;\">
	<br />
	<p><a title=\"Go to profile page\" href=\"".$pages["home"]->getAddress()."post/$postId\"><img src=\"".$pages["home"]->getAddress()."post/$postId/badge\" /></a></p>
	<textarea onClick=\"this.focus();this.select()\" style=\"overflow: hidden; height: 62px;\" readonly=\"readonly\"><a title=\"Go to profile page\" href=\"".$pages["home"]->getAddress()."post/$postId\"><img src=\"".$pages["home"]->getAddress()."post/$postId/badge\" /></a></textarea>
	</div>";
	
	if ($postHasCitation == TRUE  || $editorsPicksStatus == TRUE ) {
		print "<div class=\"center-text\">";
		if ($editorsPicksStatus == TRUE) {
			print "<div class=\"editors-mark-content\" title=\"Recommended by our editors\">Editor's Pick</div>";
		}
		if ($postHasCitation == TRUE) {
			print "<div class=\"citation-mark-content\" title=\"Post citing a peer-reviewed source\">Citation</div>";
		}
		print "</div>";
	}
	
	print "</div>
	
	<div id=\"post-summary\">
	<div class=\"margin-bottom\" title=\"Summary\">$postSummary</div>";
	// Add citations to summary if available
	if ($postHasCitation == TRUE) {
		print "<div class=\"citation-wrapper\">";
		foreach ($postCitations as $citation) {
			print "<p>".$citation["text"]."</p>";
		}
		print '</div>';
	}
	
	print "<p>";
	foreach ($postTopics as $a => $postTopic) {
		if ($a == 0) {
			print "<h4>Topics</h4>";
		}
		else {
			print ", ";
		}
		print "<a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=topic&amp;value0=".urlencode($postTopic)."\" title=\"View all posts in $postTopic\">$postTopic</a>";
	}
	
	print "</p>
	
	<div class=\"recs\">
		<div class=\"recommendation-wrapper\">";
			if ($recStatus == TRUE) print "<div class=\"recommended\" title=\"Remove recommendation and note\"></div>";
			else print "<div class=\"recommend\" title=\"Recommend\"></div>";
			print "<span class=\"rec-count\">$recCount</span>
		</div>
		<div class=\"note-button\" data-number=\"$commentCount\">
		 <div class=\"note-icon\" title=\"Notes\"></div><span class=\"note-count\">$commentCount</span>
		</div>
	</div>
	
	</div>
	
	</div>
	
	<hr class=\"margin-bottom\" />";
	
	displayNotes ($postId, $db);
	
	if (!empty($authUserId)) {
		if ($recStatus == TRUE) {
			print "<div class=\"rec-comment\" style=\"display: block\">";
		}
		else {
			print "<div class=\"rec-comment\">";
		}
		print "<div class=\"text-area\">
		<form method=\"post\" enctype=\"multipart/form-data\">
		<div class=\"subtle-text margin-bottom-small\"><span class=\"ss-bold\">Leave a note!</span><span class=\"alignright\"><span style=\"color: #383838;\" class=\"charsLeft\">102</span> characters left.</span></div>
		<p><textarea class=\"note-area\" name=\"comment\"></textarea></p>
		<div style=\"display: none;\" class=\"tweet-preview-area margin-bottom\"><div class=\"subtle-text margin-bottom-small ss-bold\">Tweet Preview</div><div class=\"tweet-preview\"><span class=\"tweet-message\"></span><span class=\"tweet-extras\"></span></div></div>
		<input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"store\" value=\"Submit\" />";
		if (!empty($twitterStatus)) {
			print " <span class=\"subtle-text alignright\" title=\"The blog's twitter handle and post's url will be included in your tweet.\"><input class=\"tweet-note\" type=\"checkbox\" value=\"true\" /> Tweet this note.</span>";
		}
		else {
			print " <a class=\"alignright subtle-text\" href=\"".$pages["twitter"]->getAddress()."/?url=$currentUrl\">Sync with Twitter</a>";
		}
		print "</form>
		<br />
		</div> 
		<div class=\"comment-notification\"></div>";
		if ($userPriv > 0) {
			$currentUrl = getURL();
			print "<div class=\"toggle-button\">Related Image</div>
							<div class=\"ss-slide-wrapper padding-content\" style=\"display: none;\">
								<div id=\"filter-panel\">
									<form method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$currentUrl&type=header\" enctype=\"multipart/form-data\">
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
		print "</div>";
	}
	print "</div>";
}
?>