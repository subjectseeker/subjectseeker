#!/usr/local/bin/php

<?php

/*
Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

header('Content-Type: text/html; charset=utf-8');

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/../scripts/util.php");

$db = ssDbConnect();

$tweets = getTwitterList($twitterListId, 80, 1);
//var_dump($tweets);
$socialNetworkUsers = getPendingSocialNetworkUsers($db, 5);
foreach ($socialNetworkUsers as $socialNetworkUser) {
	$userTweets = getTwitterUserTweets($socialNetworkUser["socialNetworkUserExtId"], $socialNetworkUser["oauthToken"], $socialNetworkUser["oauthSecretToken"]);
	//var_dump($userTweets);
	$tweets = array_merge($tweets, $userTweets);
}

//var_dump($tweets);

foreach ($tweets as $tweet) {
	$tweetTwitterId = $tweet->id_str;
	$tweetUserId = $tweet->user->id_str;
	$tweetAuthor = $tweet->user->screen_name;
	$tweetAvatar = $tweet->user->profile_image_url;
	$tweetDate = dateStringToSql($tweet->created_at);
	$tweetText = $tweet->text;
	$socialNetworkUserId = addSocialNetworkUser(1, $tweetUserId, $tweetAuthor, $tweetAvatar, NULL, NULL, NULL, NULL, NULL, $db);
	addTweet($tweetTwitterId, $tweetText, $tweetDate, $socialNetworkUserId, $db);
	
	var_dump("<br />",$tweet,"<br />");
	
	$socialNetworkUser = getSocialNetworkUser (1, $socialNetworkUserId, "socialNetworkUserId", $db);
	if ($socialNetworkUser["userId"]) {
		foreach ($tweet->entities->urls as $url) {
			$link = expandURL($url->expanded_url);
			$api = new API;
			$api->searchDb("filter0=url&modifier0=all&value0=$link", FALSE, "post", FALSE);
			
			if ($api->total > 0) {
				$post = array_shift($api->posts);
				
				$postId = $post["postId"];
				$commentId = addComment($postId, 1, 2, $socialNetworkUser["userId"], $tweetText, $tweetDate, $db);
				print "<p>$link</p>";
			}
		}
	}
}

function getPendingSocialNetworkUsers($db, $limit=20) {
	// status 0 => active
	$sql = "SELECT * FROM SOCIAL_NETWORK_USER WHERE USER_ID IS NOT NULL AND SOCIAL_NETWORK_ID = '1' ORDER BY CRAWLED_DATE_TIME LIMIT $limit";
	$results = mysql_query($sql, $db);
		
	$socialNetworkUsers = array();
	while ($row = mysql_fetch_array($results)) {
		$socialNetworkUser["socialNetworkUserId"] = $row["SOCIAL_NETWORK_USER_ID"];
		$socialNetworkUser["socialNetworkUserExtId"] = $row["SOCIAL_NETWORK_USER_EXT_ID"];
		$socialNetworkUser["socialNetworkUserName"] = $row["SOCIAL_NETWORK_USER_NAME"];
		$socialNetworkUser["socialNetworkUserAvatar"] = $row["SOCIAL_NETWORK_USER_AVATAR"];
		$socialNetworkUser["userId"] = $row["USER_ID"];
		$socialNetworkUser["oauthToken"] = $row["OAUTH_TOKEN"];
		$socialNetworkUser["oauthSecretToken"] = $row["OAUTH_SECRET_TOKEN"];
		array_push($socialNetworkUsers, $socialNetworkUser);
		
		$sql = "UPDATE SOCIAL_NETWORK_USER SET CRAWLED_DATE_TIME=NOW() WHERE SOCIAL_NETWORK_USER_ID='".$socialNetworkUser["socialNetworkUserId"]."'";
		mysql_query($sql, $db);
	}
	
	return $socialNetworkUsers;
}

function expandURL($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 6); // times out after 6s
	if(curl_exec($ch) != false) {
		$response = curl_exec($ch);
		if($response != false) {
			$responseInfo = curl_getinfo($ch);
			if($responseInfo['http_code'] == 200) {
				curl_close($ch);
				return $responseInfo['url'];
			}
		}
	}
}

function addTweet($tweetTwitterId, $tweetText, $tweetDate, $socialNetworkUserId, $db) {
	$tweetText = mysql_real_escape_string($tweetText);
	
	$sql = "INSERT IGNORE INTO TWEET (TWEET_TWITTER_ID, TWEET_TEXT, TWEET_DATE_TIME, SOCIAL_NETWORK_USER_ID) VALUES ('$tweetTwitterId', '$tweetText', '$tweetDate', '$socialNetworkUserId')";
	var_dump($sql, mysql_query($sql, $db));
	
	return $tweetId = mysql_insert_id;
}

?>