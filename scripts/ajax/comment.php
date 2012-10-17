<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once (dirname(__FILE__)."/../initialize.php");

global $imagesUrl;
global $twitterListId;
global $twitterListToken;
global $twitterListTokenSecret;
global $twitterNotesToken;
global $twitterNotesTokenSecret;

$db = ssDbConnect();

if (isLoggedIn()){
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserName = $authUser->userName;
	$userTwitter = getUserSocialAccount(1, $authUserId, $db);
}
	
$postId = str_replace("post-", "", $_REQUEST["postId"]);
if (!empty($_REQUEST["step"])) {
	$step = $_REQUEST["step"];
}
if (!empty($_REQUEST["tweet"])) {
	$tweet = $_REQUEST["tweet"];
}
if (!empty($_REQUEST["comment"])) {
	$note = $_REQUEST["comment"];
}
if (!empty($_REQUEST["tweetContent"])) {
	$tweetContent = $_REQUEST["tweetContent"];
}
$overwriteStatus = FALSE;

// Check if a comment must be stored
if ($step == "store" || $step == "confirm") {
	if ($step != "confirm") {
		foreach (getComments($postId, $db) as $item) {
			$commentUserId = $item["userId"];
			if ($commentUserId == $authUserId) {
				$overwriteStatus = TRUE;
			}
		}
	}
	if ($overwriteStatus == TRUE) {
		print "<h3>Confirmation Message</h3>
		<form method=\"post\">
		<p>You already have a note on this post. Are you sure you want to overwrite it?</p>
		<p><input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"confirm\" value=\"Yes\" /> <input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"dont-update\" value=\"No\" /></p>
		</form>";
		return;
	}
	else {
		$comment = mysql_real_escape_string(strip_tags($note));
		$sql = "UPDATE POST_RECOMMENDATION SET REC_COMMENT = '$comment' WHERE BLOG_POST_ID = '$postId' AND USER_ID = '$authUserId'";
		mysql_query($sql, $db);
	}
	
	global $debugSite;
	
	if ($debugSite != "true") {
		// Use Search API to find Blog ID and Post URL
		$errormsgs = array();
		$queryList = httpParamsToSearchQuery("type=post&filter0=identifier&value0=$postId");
		$settings = httpParamsToExtraQuery("type=post&filter0=identifier&value0=$postId");
		$postData = generateSearchQuery ($queryList, $settings, 0, $errormsgs, $db);
		$row = mysql_fetch_array($postData);
		$postUri = $row["BLOG_POST_URI"];
		$blogId = $row["BLOG_ID"];
		
		// Get Blog social info
		$blogSocialAccount = getBlogSocialAccount(1, $blogId, $db);
		$blogTwitterHandle = $blogSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"];
		
		// Tweet note to our Twitter account.
		$shortUrl = get_bitly_short_url($postUri,$bitlyUser,$bitlyKey);
		
		$noteAuthor = $authUserName;
		if (!empty($userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"])) {
			$noteAuthor = "@".$userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"];
		}
		$ssNote = "$note $shortUrl —$noteAuthor";
		
		$connection = getTwitterAuthTokens ($twitterNotesToken, $twitterNotesTokenSecret);
		$connection->post('statuses/update', array('status' => $ssNote));
	}
		
	// If the option is checked, tweet from user's account.
	if ($tweet == "true") {
		$connection = getTwitterAuthTokens ($userSocialAccount['OAUTH_TOKEN'], $userSocialAccount['OAUTH_SECRET_TOKEN']);
		$result = $connection->post('statuses/update', array('status' => $tweetContent));
		
		print "<iframe style=\"display: none;\" src=\"/sync/twitter/?note=".urlencode($note)."\"></iframe>";
	}
}

if ($step == "showComments") {
	displayNotes ($postId, $db);
}
?>