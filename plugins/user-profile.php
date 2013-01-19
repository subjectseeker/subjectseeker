<?php
/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function displayUserProfile() {
	global $homeUrl;
	
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
	preg_match('/(?<=\/user\/)[A-Za-z][A-Za-z0-9_]+/', $_SERVER["REQUEST_URI"], $matchResult);
	$userName = $matchResult[0];
	
	$userId = getUserId($userName, $db);
	$userAvatar = getUserAvatar($userId, $db);
	$userBannerSrc = getUserBanner($userId, $db);
	$userData = getUserData($userId, $db);
	$userDisplayName = $userData["userDisplayName"];
	$userUrl = $userData["userUrl"];
	$userLocation = $userData["userLocation"];
	$userBio = strip_tags($userData["userBio"], "<a>");
	
	$api = new API;
	$api->searchDb("filter0=author&modifier0=user-name&value0=$userName", FALSE, "blog", FALSE);
	$sites = $api->sites;
	
	if (!$userId) {
		print "<p class=\"ss-warning\">User ($userName) does not exist.</p>";
		return NULL;
	}
	
	print "<div class=\"profile-title\">
	<h1>$userName</h1>
	<div class='profile-buttons'>";
	recButton($userId, 2, $authUserId, FALSE, $db);
	followButton($userId, 2, $authUserId, $db);
	print "</div>
	</div>
	
	<div class=\"profile-banner\"><img src=\"$userBannerSrc\" alt=\"$userName's banner\" /></div>
	
	<div class='profile-sidebar'>
	<div class='profile-avatar'><img src='".$userAvatar["big"]."' title='User avatar' /></div>
	<h3>About $userName</h3>
	<div class='block'>
	<div class='block-label'>Name</div>
	<div class='block-value'>$userDisplayName</div>";
	if ($userUrl) {
		print "<div class='block-label'>URL</div>
		<div class='block-value'><a href='$userUrl'>$userUrl</a></div>";
	}
	if ($userLocation) {
		print "<div class='block-label'>Location</div>
		<div class='block-value'>$userLocation</div>";
	}
	print "</div>";
	$followers = getFollowers($userId, 2, $authUserId, $db);
	if ($followers) {
		print "<h3>Followers (".count($followers).")</h3>
		<div class=\"margin-bottom\">";
		foreach ($followers as $follower) {
			$followerUserId = $follower["userId"];
			$followerUserName = getUserName($followerUserId, $db);
			$followerUserAvatar = getUserAvatar($followerUserId, $db);
			print "<a class=\"follower\" href=\"$homeUrl/user/$followerUserName\"><img height=\"25\" src=\"".$followerUserAvatar["small"]."\" /> <span>$followerUserName</span></a>";
		}
		print "</div>";
	}
	if ($sites) {
		print "<h3>$userName's Sites</h3>
		<div class='block'>
		<ul>";
		foreach ($sites as $site) {
			displaySite($site, $db);
		}
		print "</ul>
		</div>";
	}
	print "</div>
	
	<div class='profile-main'>";
	if ($userBio) {
		print "<h3>Biography</h3>
		<div class='block user-bio'>$userBio</div>";
	}
	print "<div class='user-activity'>
	<h3>$userName's Activity</h3>
	<div class='user-actions'>";
	userActivity(NULL, NULL, $userId, NULL, 30, $db);
	print "</div>
	</div>
	</div>";
}

function getUserBanner($userId, $db) {
	$sql = "SELECT USER_BANNER FROM USER_PREFERENCE WHERE USER_ID = '$userId'";
	$result = mysql_query($sql, $db);
	if ($result == NULL || mysql_num_rows($result) == 0) {
		return NULL;
	}
	$row = mysql_fetch_array($result);
	$bannerName = $row["USER_BANNER"];
	
	global $homeUrl;
	if ($bannerName) {
		$banner = "$homeUrl/images/users/$userId/banners/$bannerName";
	} else {
		$banner = "$homeUrl/images/misc/default-user-banner.jpg";
	}
	
	return $banner;
}

?>