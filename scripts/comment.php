<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once "ss-util.php";

global $imagesUrl;
global $twitterConsumerKey;
global $twitterConsumerSecret;
global $twitterListId;
global $twitterListToken;
global $twitterListTokenSecret;
global $twitterNotesToken;
global $twitterNotesTokenSecret;
global $wpLoad;

include_once $wpLoad;

$db = ssDbConnect();

if (is_user_logged_in()) {
	// Connect to database
	global $current_user;
	get_currentuserinfo();
	$displayName = $current_user->user_login;
	$email = $current_user->user_email;
	$userId = addUser($displayName, $email, $db);
	$userSocialAccount = getUserSocialAccount(1, $userId, $db);
}
	
$postId = $_REQUEST["id"];
$step = $_REQUEST["step"];
$tweet = $_REQUEST["tweet"];
$note = $_REQUEST["comment"];
$tweetContent = $_REQUEST["tweetContent"];

// Check if a comment must be stored
if ($step == "store" || $step == "confirm") {
	if ($step != "confirm") {
		foreach (getComments($postId, $db) as $item) {
			$commentUserId = $item["userId"];
			if ($commentUserId == $userId) {
				$overwriteStatus = TRUE;
			}
		}
	}
	if ($overwriteStatus == TRUE) {
		print "<h3>Confirmation Message</h3>
		<form method=\"POST\">
		<p>You already have a note on this post. Are you sure you want to overwrite it?</p>
		<p><input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"confirm\" value=\"Yes\" /> <input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"dont-update\" value=\"No\" /></p>
		</form>";
		return;
	}
	else {
		$comment = mysql_real_escape_string($note);
		$sql = "UPDATE RECOMMENDATION SET REC_COMMENT = '$comment' WHERE BLOG_POST_ID = $postId AND USER_ID = $userId";
		mysql_query($sql, $db);
	}
	
	// Use Search API to find Blog ID and Post URL
	$errormsgs = array();
	parse_str("type=post&filter0=identifier&value0=$postId", $parsedQuery);
	$queryList = httpParamsToSearchQuery($parsedQuery);
	$settings = httpParamsToExtraQuery($parsedQuery);
	$postData = generateSearchQuery ($queryList, $settings, 0, $errormsgs, $db);
	$row = mysql_fetch_array($postData);
	$postUri = $row["BLOG_POST_URI"];
	$blogId = $row["BLOG_ID"];
	
	// Get Blog social info
	$blogSocialAccount = getBlogSocialAccount(1, $blogId, $db);
	$blogTwitterHandle = $blogSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"];
	
	// Tweet note to our Twitter account.
	$shortUrl = get_bitly_short_url($postUri,$bitlyUser,$bitlyKey);
	
	$noteAuthor = $displayName;
	if ($userSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"]) {
		$noteAuthor = "@".$userSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"];
	}
	$ssNote = "$note $shortUrl —$noteAuthor";
	
	$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $twitterNotesToken, $twitterNotesTokenSecret);
	$connection->post('statuses/update', array('status' => $ssNote));
		
	// If the option is checked, tweet from user's account.
	if ($tweet == "true") {
		$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $userSocialAccount['OAUTH_TOKEN'], $userSocialAccount['OAUTH_SECRET_TOKEN']);
		$result = $connection->post('statuses/update', array('status' => $tweetContent));
		
		print "<iframe style=\"display: none;\" src=\"/sync/twitter/?note=".urlencode($note)."\"></iframe>";
	}
}

if ($step == "showComments") {
	// Get comments list
	$commentList = getComments($postId, $db);
	$commentCount = count($commentList);
	
	print "<h3>Notes</h3>
	<div id=\"comment-list\" data-count=\"$commentCount\">";
	
	if ($commentList == NULL) {
		print "<div id=\"padding-content\">There are no notes for this post. Recommend this post to leave a note.</div>";
	}
	else {
		// Display comments
		foreach ($commentList as $data) {
			$commentUserId = $data["userId"];
			$postDate = $data["date"];
			$comment = $data["comment"];
			$displayName = getUserName($commentUserId, $db);
			print "<div class=\"comment-wrapper\" data-commenterUserId=\"$commentUserId\">
			<div class=\"comment-header\">$displayName<span class=\"alignright\">$postDate</span></div>
			<br />
			<div id=\"padding-content\">$comment</div>
			</div>";
		}
		print "</div>";
	}
}
?>