<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/../scripts/util.php");

header("Content-type: text/html; charset=utf-8");
$db = ssDbConnect();

/* Transfer Recommendations */

$sql = "SELECT * FROM POST_RECOMMENDATION";
$results = mysql_query($sql, $db);

while ($row = mysql_fetch_array($results)) {
	$postId = $row["BLOG_POST_ID"];
	$userId = $row["USER_ID"];
	$recDate = $row["REC_DATE_TIME"];
	$recImage = $row["REC_IMAGE"];
	$recNotification = $row["REC_NOTIFICATION"];
	$recComment = $row["REC_COMMENT"];
	
	$sql = "INSERT IGNORE INTO RECOMMENDATION (OBJECT_ID, USER_ID, OBJECT_TYPE_ID, REC_DATE_TIME, REC_IMAGE, REC_NOTIFICATION) VALUES ('$postId', '$userId', '1', '$recDate', '$recImage', '$recNotification')";
	mysql_query($sql, $db);
	
	if ($recComment) {
		$sql = "INSERT IGNORE INTO COMMENT (OBJECT_ID, USER_ID, OBJECT_TYPE_ID, COMMENT_SOURCE_ID, COMMENT_DATE_TIME, COMMENT_TEXT) VALUES ('$postId', '$userId', '1', '1', '$recDate', '$recComment')";
		mysql_query($sql, $db);
	}
}

/* Transfer Social Network Accounts */

$sql = "SELECT * FROM USER_SOCIAL_ACCOUNT";
$results =	mysql_query($sql, $db);

$socialNetworkUsers = array();
while ($row = mysql_fetch_array($results)) {
	$socialNetworkUserName = $row["SOCIAL_NETWORKING_ACCOUNT_NAME"];
	$socialNetworkUsers[$socialNetworkUserName]["name"] = $socialNetworkUserName;
	$socialNetworkUsers[$socialNetworkUserName]["userId"] = $row["USER_ID"];
	$socialNetworkUsers[$socialNetworkUserName]["access_token"] = $row["OAUTH_TOKEN"];
	$socialNetworkUsers[$socialNetworkUserName]["secret_token"] = $row["OAUTH_SECRET_TOKEN"];
	
	$twitterNames[] = $socialNetworkUserName;
}

$sql = "SELECT * FROM BLOG_SOCIAL_ACCOUNT";
$results =	mysql_query($sql, $db);
while ($row = mysql_fetch_array($results)) {
	$socialNetworkUserName = $row["SOCIAL_NETWORKING_ACCOUNT_NAME"];
	$socialNetworkUsers[$socialNetworkUserName]["name"] = $socialNetworkUserName;
	$socialNetworkUsers[$socialNetworkUserName]["blogId"] = $row["BLOG_ID"];
	
	$twitterNames[] = $socialNetworkUserName;
}

$twitterNameLists = array_chunk($twitterNames, 80);
foreach ($twitterNameLists as $twitterNameList) {
	$twitterNames = implode(",", $twitterNameList);
	$twitterUsers = getTwitterUserDetails(NULL, $twitterNames);
	foreach ($twitterUsers as $twitterUser) {
		$twitterUserId = $twitterUser->id;
		$twitterUserName = $twitterUser->screen_name;
		$twitterUserAvatar = $twitterUser->profile_image_url;
		$oauthToken = $socialNetworkUsers[$twitterUserName]["access_token"];
		$oauthSecretToken = $socialNetworkUsers[$twitterUserName]["secret_token"];
		$userId = $socialNetworkUsers[$twitterUserName]["userId"];
		$blogId = $socialNetworkUsers[$twitterUserName]["blogId"];
		
		$socialNetworkUserId = addSocialNetworkUser(1, $twitterUserId, $twitterUserName, $twitterUserAvatar, $userId, $blogId, $oauthToken, $oauthSecretToken, NULL, $db);
	}
}

/* Transfer Registration dates */

$sql = "UPDATE USER SET REGISTRATION_DATE_TIME = '2012-10-01 00:00:00'";
mysql_query($sql, $db);

$sql = "SELECT author.USER_ID, blog.ADDED_DATE_TIME FROM USER user INNER JOIN BLOG_AUTHOR author ON user.USER_ID = author.USER_ID INNER JOIN BLOG blog ON author.BLOG_ID = blog.BLOG_ID GROUP BY author.USER_ID ORDER BY blog.ADDED_DATE_TIME";
$results = mysql_query($sql, $db);

while ($row = mysql_fetch_array($results)) {
	$userId = $row["USER_ID"];
	$addedDate = $row["ADDED_DATE_TIME"];
	
	$sql = "UPDATE USER SET REGISTRATION_DATE_TIME = '$addedDate' WHERE USER_ID = '$userId'";
	mysql_query($sql, $db);
}

?>
