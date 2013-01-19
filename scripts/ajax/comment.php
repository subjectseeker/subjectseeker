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
	$userTwitter = getSocialNetworkUser(1, $authUserId, "userId", $db);
}
	
$objectId = $_REQUEST["id"];
$objectTypeId = $_REQUEST["type"];
$step = $_REQUEST["step"];
$tweet = $_REQUEST["tweet"];
$commentText = $_REQUEST["comment"];
$tweetContent = $_REQUEST["tweetContent"];

$commentDate = dateStringToSql("now");
$commentId = addComment($objectId, $objectTypeId, 1, $authUserId, $commentText, $commentDate, $db);
$comment = getComment($commentId, $db);
displayComment($comment, $authUserId, $db);

global $debugSite;
if ($debugSite != "true") {
	// Use Search API to find Blog ID and Post URL
	$api = new API;
	$api->searchDb("type=post&filter0=identifier&value0=$postId", FALSE, "post", FALSE);
	$post = array_shift($api->posts);
	$postUrl = $post["postId"];
	$blogId = $post["siteId"];
		
	// Get Blog social info
	$socialNetworkUser = getSocialNetworkUser(1, $blogId, "siteId", $db);
	$blogTwitterHandle = $socialNetworkUser["socialNetworkUserName"];
	
	// Tweet note to our Twitter account.
	$shortUrl = get_bitly_short_url($postUri);
	
	$noteAuthor = $authUserName;
	if (!empty($userTwitter["socialNetworkUserName"])) {
		$noteAuthor = "@".$userTwitter["socialNetworkUserName"];
	}
	$ssNote = "$note $shortUrl —$noteAuthor";
	
	$connection = getTwitterAuthTokens($twitterNotesToken, $twitterNotesTokenSecret);
	$connection->post("statuses/update", array("status" => $ssNote));
}
	
// If the option is checked, tweet from user's account.
if ($tweet == "true") {
	$connection = getTwitterAuthTokens ($userSocialAccount["OAUTH_TOKEN"], $userSocialAccount["OAUTH_SECRET_TOKEN"]);
	$result = $connection->post("statuses/update", array("status" => $tweetContent));
}

?>