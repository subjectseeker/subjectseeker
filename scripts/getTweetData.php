<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

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