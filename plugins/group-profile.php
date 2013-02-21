<?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayGroupProfile() {
	global $homeUrl;
	global $sitename;
	global $pages;
	
	$db = ssDbConnect();
	
	$authUserId = NULL;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
	}
	
	preg_match('/(?<=\/group\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$groupId = $matchResult[0];
	
	$group = getGroup($groupId, $db);
	$groupBannerSrc = getGroupBanner($groupId, $db);
	
	if (!$group) {
		return print "<p class='ss-warning'>Group ID ($groupId) not found in our system.</p>";
	}
	
	$groupId = $group["groupId"];
	$groupName = $group["groupName"];
	$groupDescription = strip_tags($group["groupDescription"]);
	$groupCreationDate = $group["groupCreationDate"];
	
	print "<div class=\"profile-title\">
	<h1>$groupName</h1>
	<div class='profile-buttons'>";
	recButton($groupId, 4, $authUserId, FALSE, $db);
	commentButton($groupId, 4, $db);
	followButton($groupId, 4, $authUserId, $db);
	print"</div>
	</div>
	
	<div class=\"profile-banner\"><img src=\"$groupBannerSrc\" alt=\"$groupName's banner\" /></div>
	
	<div class=\"profile-sidebar\">";
	$managers = getGroupManagers($groupId, NULL, $db);
	print "<h3>Managers (".count($managers).")</h3>
	<div class=\"margin-bottom\">";
	foreach ($managers as $manager) {
		$userId = $manager["userId"];
		$userName = getUserName($userId, $db);
		$userAvatar = getUserAvatar($userId, $db);
		print "<div class=\"user-card-small\" data-id=\"$userId\"><a href=\"$homeUrl/user/$userName\"><img height=\"25\" src=\"".$userAvatar["small"]."\" /> <span>$userName</span></a></div>";
	}
	print "</div>";
	
	$followers = getFollowers($groupId, 4, $authUserId, $db);
	if ($followers) {
		print "<h3>Followers (".count($followers).")</h3>
		<div class=\"margin-bottom\">";
		foreach ($followers as $follower) {
			$userId = $follower["userId"];
			$userName = getUserName($userId, $db);
			$userAvatar = getUserAvatar($userId, $db);
			print "<div class=\"user-card-small\" data-id=\"$userId\"><a href=\"$homeUrl/user/$userName\"><img height=\"25\" src=\"".$userAvatar["small"]."\" /> <span>$userName</span></a></div>";
		}
		print "</div>";
	}
	$api = new API;
	$api->searchDb("filter0=group&value0=$groupId&n=5&sort=added-date&order=desc", FALSE, "blog", FALSE);
	if ($api->sites) {
		print "<h3>Sites</h3>";
		displaySites($api->sites, $db);
		print "</div>";
	}
	
	print "<div class=\"profile-main\">
	<h3>Description</h3>
	<div class=\"block profile-description\" title=\"Description\">$groupDescription</div>";
	$api = new API;
	$api->searchDb("filter0=group&value0=$groupId&n=8", FALSE, "post", FALSE);
	if ($api->posts) {
		print "<h3>Latest Posts</h3>
		<div class=\"entries\">";
		displayPosts ($api->posts, TRUE, FALSE, $db);
		print "</div>
		<div class=\"page-buttons\">
		<a class=\"ss-button\" href=\"".$pages["posts"]->address."/?filter0=group&value0=$groupId\">More</a>
		</div>";
	}
	
	$addTags = FALSE;
	if (isGroupManager($groupId, $authUserId, NULL, $db)) {
		$addTags = TRUE;
	}
	print "<h3>Tags</h3>";
	displayTags($groupId, 4, $addTags, $db);
	
	displayComments($groupId, 4, $authUserId, $db);
	print "</div>";
}

function getGroupBanner($groupId, $db) {
	$sql = "SELECT GROUP_BANNER FROM `GROUP` WHERE GROUP_ID = '$groupId'";
	$result = mysql_query($sql, $db);
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($result);
	$bannerName = $row["GROUP_BANNER"];
	
	global $homeUrl;
	if ($bannerName) {
		$banner = "$homeUrl/images/groups/$groupId/banners/$bannerName";
	} else {
		$banner = "$homeUrl/images/misc/default-group-banner.jpg";
	}
	
	return $banner;
}

?>