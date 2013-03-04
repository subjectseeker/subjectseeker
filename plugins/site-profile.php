<?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displaySiteProfile() {
	global $homeUrl, $pages;
	
	if (isset($_REQUEST["step"]))
		$step = $_REQUEST["step"];
	
	$db = ssDbConnect();
	$authUserId = NULL;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
	}
	preg_match('/(?<=\/site\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$siteId = $matchResult[0];
	
	$site = getSite($siteId, $db);
	$siteName = $site["siteName"];
	$siteUrl = $site["siteUrl"];
	$siteFeedUrl = $site["siteFeedUrl"];
	$siteSummary = $site["siteSummary"];
	$siteTwitter = getSocialNetworkUser(1, $siteId, "siteId", $db);
	$siteBanner = getSiteBanner($siteId, $db);
	$siteTopics = getTags($siteId, 3, 1, $db);
	
	if (!$site) {
		print "<p class=\"ss-warning\">Site (ID: $siteId) does not exist.</p>";
		return NULL;
	}
	
	print "<div class=\"profile-title\">
	<h1>$siteName</h1>
	<div class='profile-buttons'>";
	recButton($siteId, 3, $authUserId, FALSE, $db);
	followButton($siteId, 3, $authUserId, $db);
	print "</div>
	</div>
	
	<div class=\"profile-banner\"><img src=\"$siteBanner\" alt=\"$siteName's banner\" /></div>
	
	<div class='profile-sidebar'>
	<div class='block'>
	<h3>About $siteName</h3>
	<div class='block-label'>Name</div>
	<div class='block-value'>$siteName</div>
	<div class='block-label'>Categories</div>
	<div class='block-value'>";
	foreach ($siteTopics as $i => $siteTopic) {
		if ($i != 0) {
			print ", ";
		}
		print "<a href=\"".$pages["sources"]->getAddress()."/?type=blog&amp;filter0=topic&amp;value0=".urlencode($siteTopic["topicName"])."\" title=\"View all posts in ".$siteTopic["topicName"]."\">".$siteTopic["topicName"]."</a>";
	}
	print "</div>
	<div class='block-label'>URL</div>
	<div class='block-value'><a href='$siteUrl'>$siteUrl</a></div>
	<div class='block-label'>Feed</div>
	<div class='block-value'><a href='$siteFeedUrl'>$siteFeedUrl</a></div>";
	if ($siteTwitter) {
		print "<div class='block-label'>Social Networks</div>
		<ul>
		<li class=\"sync-link\"><a title=\"Twitter account\" href=\"https://twitter.com/#!/".$siteTwitter["socialNetworkUserName"]."\"><div class=\"twitter-icon\"></div> ".$siteTwitter["socialNetworkUserName"]."</a></li>
		</ul>";
	}
	print "<br />
	<div class=\"center-text\">
	<a class=\"ss-button\" href=\"$homeUrl/claim/".$siteId."\">Claim this site</a>
	</div>
	</div>";
	
	$followers = getFollowers($siteId, 3, $authUserId, $db);
	if ($followers) {
		print "<h3>Followers (".count($followers).")</h3>
		<div class=\"margin-bottom\">";
		foreach ($followers as $follower) {
			$followerUserId = $follower["userId"];
			$followerUserName = getUserName($followerUserId, $db);
			$followerUserAvatar = getUserAvatar($followerUserId, $db);
			print "<div class=\"user-card-small\"><a href=\"$homeUrl/user/$followerUserName\"><img height=\"25\" src=\"".$followerUserAvatar["small"]."\" /> <span>$followerUserName</span></a></div>";
		}
		print "</div>";
	}
	$authorList = getAuthorList($siteId, FALSE, $db);
	if (mysql_num_rows($authorList) != 0) {
		print "<ul class=\"block\">";
		print "<h3>Authors</h3>";
		while ($row = mysql_fetch_array($authorList)) {
			print "<li><a class=\"author-link\" href=\"".$pages["posts"]->address."/?type=post&amp;filter0=author&amp;modifier0=author-id&amp;value0=".$row["BLOG_AUTHOR_ID"]."\">".$row["BLOG_AUTHOR_ACCOUNT_NAME"]."</a>";
			if ($row["USER_ID"]) {
				$authorUserId = $row["USER_ID"];
				$authorUserName = getUserName($authorUserId, $db);
				$authorUserAvatar = getUserAvatar($authorUserId, $db);
				print "<a class=\"author-user\" href=\"$homeUrl/user/$authorUserName\">$authorUserName</a>";
			}
			print "</li>";
		}
		print "</ul>";
	}
	print "</div>
	
	<div class='profile-main'>";
	if ($siteSummary) {
		print "<h3>Description</h3>
		<div class='block profile-description'>$siteSummary</div>";
	}
	print "<div class='user-activity'>
	<h3>$siteName's Latest Posts</h3>
	<div class='user-actions'>
	<div class=\"entries\">";
	$api = new API;
	$api->searchDb("filter0=blog&modifier0=identifier&value0=$siteId&n=5", FALSE, "post");
	displayPosts ($api->posts, TRUE, FALSE, $db);
	print "</div>
	<div class=\"page-buttons\">
	<a class=\"ss-button\" href=\"".$pages["posts"]->address."/?type=post&amp;filter0=blog&amp;modifier0=identifier&amp;value0=$siteId\">More</a>
	</div>
	</div>
	</div>
	<h3>Tags</h3>";
	displayTags($siteId, 3, TRUE, $db);
	displayComments($siteId, 3, $authUserId, $db);
	print "</div>";
}

function getSiteBanner($siteId, $db) {
	$sql = "SELECT BLOG_BANNER FROM BLOG WHERE BLOG_ID = '$siteId'";
	$result = mysql_query($sql, $db);
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($result);
	$bannerName = $row["BLOG_BANNER"];
	
	global $homeUrl;
	if ($bannerName) {
		$banner = "$homeUrl/images/sites/$siteId/banners/$bannerName";
	} else {
		$banner = "$homeUrl/images/misc/default-user-banner.jpg";
	}
	
	return $banner;
	
}

?>