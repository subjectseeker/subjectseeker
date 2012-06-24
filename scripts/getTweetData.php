<?php
include_once "ss-util.php";
global $wpLoad;
include_once $wpLoad;

$db = ssDbConnect();
	
$postId = $_REQUEST["id"];
	
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

$shortUrl = get_bitly_short_url($postUri,$bitlyUser,$bitlyKey);

if ($blogTwitterHandle) {
	$blogTwitterHandle = " @".$blogTwitterHandle;
}

print " $blogTwitterHandle <a href=\"$shortUrl\">$shortUrl</a>";
?>