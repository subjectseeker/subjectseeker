<?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayPostProfile() {
	global $homeUrl;
	global $sitename;
	global $pages;
	
	if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	
	$db = ssDbConnect();
	
	$authUserId = NULL;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
	}
	
	preg_match('/(?<=\/post\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$postId = $matchResult[0];
	
	$api = new API;
	$api->searchDb("type=post&filter0=identifier&value0=$postId", FALSE, "post");
	$post = array_shift($api->posts);
	
	if (!$post) {
		return print "<p class='ss-warning'>Post ID ($postId) not found in our system.</p>";
	}
	
	$postId = $post["postId"];
	$blogName = $post["siteName"];
	$blogUri = $post["siteUrl"];
	$postDate = strtotime($post["postDate"]);
	$postSummary = strip_tags($post["postSummary"]);
	$postTitle = $post["postTitle"];
	$postUri = $post["postUrl"];
	$postProfile = "/post/" . $postId;
	$postHasCitation = $post["hasCitation"];
	$date = date("g:i A | F d, Y", $postDate);
	$postAuthor = $post["postAuthorName"];
	$postLanguage = postIdToLanguageName ($postId, $db);
	
	// If post doesn't have a title, use the url instead.
	if (! $postTitle)
		$postTitle = $postUri;
	
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
	if ($postHasCitation) $postCitations = postIdToCitation($postId, $db);
	
	$editorsPicksStatus = getRecommendations($postId, 1, NULL, TRUE, NULL, NULL, $db);
	
	print "<div>
	<h1><a href=\"$postUri\" target=\"_blank\" title=\"Permanent link to $postTitle\">$postTitle</a></h1>
	<div class='profile-buttons'>";
	recButton($postId, 1, $authUserId, FALSE, $db);
	commentButton($postId, 1, $db);
	print"</div>
	</div>
	<div class=\"profile-sidebar\">
	<h2 class='block-title'>About this post</h2>
	<div class=\"block\">
	<div class='block-label'>Source</div>
	<div class='block-value'><a href=\"$blogUri\" target=\"_blank\" title=\"Permanent link to $blogName homepage\" rel=\"alternate\">$blogName</a></div>";
	if ($postAuthor) {
		print "<div class='block-label'>Author</div>
		<div class='block-value'><a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=author&amp;value0=$postAuthor\">$postAuthor</a></div>";
	}
	print "<div class='block-label'>Date</div>
	<div class='block-value'>$date</div>";
	if ($postLanguage) {
		print "<div class='block-label'>Language</div>
		<div class='block-value'>$postLanguage</div>";
	}
	print "<div class='block-label'>Categories</div>
	<div class='block-value'>";
	foreach ($blogTopics as $i => $blogTopic) {
		if ($i != 0)
			print ", ";
			
		print "<a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=blog&amp;modifier0=topic&amp;value0=".urlencode($blogTopic)."\" title=\"View all posts in $blogTopic\">$blogTopic</a>";
	}
	print "</div>
	
	<div class=\"toggle-button center-text\">Link to this page</div>
	<div class=\"ss-slide-wrapper center-text\">
	<br />
	<a title=\"Visit the $sitename post profile\" href=\"$homeUrl/post/$postId\"><img src=\"$homeUrl/post/$postId/badge\" /></a>
	<br />
	<textarea onClick=\"this.focus();this.select()\" style=\"overflow: hidden; height: 62px;\" readonly=\"readonly\"><a title=\"Go to profile page\" href=\"$homeUrl/post/$postId\"><img src=\"$homeUrl/post/$postId/badge\" /></a></textarea>
	</div>";
	
	if ($postHasCitation == TRUE	|| $editorsPicksStatus == TRUE ) {
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
	</div>
	
	<div class=\"profile-main\">
	<div class=\"block\" title=\"Summary\">
	$postSummary";
	// Add citations to summary if available
	if ($postHasCitation == TRUE) {
		print "<div class=\"citation-wrapper\">";
		foreach ($postCitations as $citation) {
			print "<p>".$citation["text"]."</p>";
		}
		print '</div>';
	}
	print "</div>";
	if ($postTopics) {
		print "<h3>Topics</h3>
		<div class=\"margin-bottom\">";
		foreach ($postTopics as $a => $postTopic) {
			if ($a != 0) {
				print ", ";
			}
			print "<a href=\"".$pages["posts"]->getAddress()."/?type=post&amp;filter0=topic&amp;value0=".urlencode($postTopic)."\" title=\"View all posts in $postTopic\">$postTopic</a>";
		}
		print "</div>";
	}
	displayComments($postId, 1, $authUserId, $db);
	print "</div>
	</div>
	</div>
	</div>";
}
?>