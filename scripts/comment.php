<?php
require_once "/home/sciseek/public_html/dev/twitteroauth/twitteroauth/twitteroauth.php";
include_once "ss-util.php";
require_once "/home/sciseek/public_html/dev/wp-load.php";

global $imagesUrl;
global $twitterConsumerKey;
global $twitterConsumerSecret;
global $twitterListId;
global $twitterListToken;
global $twitterListTokenSecret;
global $twitterNotesToken;
global $twitterNotesTokenSecret;
global $bitlyUser;
global $bitlyKey;

$db = ssDbConnect();

/* returns the shortened url */
function get_bitly_short_url($url,$login,$appkey,$format='txt') {
  $connectURL = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format;
	
	$ch = curl_init();
  $timeout = 5;
  curl_setopt($ch,CURLOPT_URL,$connectURL);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  $data = curl_exec($ch);
  curl_close($ch);
	
  return $data;
}

if (is_user_logged_in()) {
	// Connect to database
	global $current_user;
	get_currentuserinfo();
	$displayName = $current_user->user_login;
	$email = $current_user->user_email;
	$userId = addUser($displayName, $email, $db);
}
	
$postId = $_REQUEST["id"];
$step = $_REQUEST["step"];
$tweet = $_REQUEST["tweet"];
$note = $_REQUEST["comment"];

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
		<p>You already have a note on this post, are you sure you want to overwrite your note?</p>
		<p><input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"confirm\" value=\"Yes\" /> <input id=\"submit-comment\" class=\"submit-comment ss-button\" type=\"button\" data-step=\"dont-update\" value=\"No\" /></p>
		</form>";
		return;
	}
	else {
		$comment = mysql_real_escape_string($note);
		$sql = "UPDATE RECOMMENDATION SET REC_COMMENT = '$comment' WHERE BLOG_POST_ID = $postId AND USER_ID = $userId";
		mysql_query($sql, $db);
	}
	
	$errormsgs = array();
	parse_str("type=post&filter0=identifier&value0=$postId", $parsedQuery);
	$queryList = httpParamsToSearchQuery($parsedQuery);
	$settings = httpParamsToExtraQuery($parsedQuery);
	$postData = generateSearchQuery ($queryList, $settings, 0, $errormsgs, $db);
	
	$row = mysql_fetch_array($postData);
	$postUri = $row["BLOG_POST_URI"];
	$blogId = $row["BLOG_ID"];
	$blogSocialAccount = getBlogSocialAccount(1, $blogId, $db);
	
	if ($blogSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"]) {
		$note = $note . " @" . $blogSocialAccount["SOCIAL_NETWORKING_ACCOUNT_NAME"];
	}
	
	$shortUrl = get_bitly_short_url($postUri,$bitlyUser,$bitlyKey);
	$note = $note . " " . $shortUrl;
	
	$connection = new TwitterOAuth($twitterConsumerKey, $twitterConsumerSecret, $twitterNotesToken, $twitterNotesTokenSecret);
	$connection->post('statuses/update', array('status' => $note));
		
	if ($tweet == "true") {
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