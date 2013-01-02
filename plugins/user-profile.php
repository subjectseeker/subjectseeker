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
	$userData = getUserData($userId, $db);
	$userDisplayName = $userData["userDisplayName"];
	$userUrl = $userData["userUrl"];
	$userLocation = $userData["userLocation"];
	$userBio = strip_tags($userData["userBio"], "<a>");
	$followerCount = getFollowers($userId, 2, NULL, $db);
	
	$api = new API;
	$api->searchDb("filter0=author&modifier0=user-name&value0=$userName", FALSE, "blog");
	$sites = $api->sites;
	
	if (!$userId) {
		print "<p class=\"ss-warning\">User ($userName) does not exist.</p>";
		return NULL;
	}
	
	print "<div>
	<h1>$userName</h1>
	<div class='profile-buttons'>";
	recButton($userId, 2, $authUserId, FALSE, $db);
	followButton($userId, 2, $authUserId, $db);
	print "</div>
	</div>
	
	<div class='profile-sidebar'>
	<div class='user-avatar'><img src='".$userAvatar["big"]."' title='User avatar' /></div>
	<h2 class='block-title'>About $userName</h2>
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
	print "<div class='block-label'>Followers</div>
	<div class='block-value'>$followerCount</div>
	</div>";
	if ($sites) {
		print "<h2 class='block-title'>$userName's Sites</h2>
		<div class='block'>
		<ul>";
		foreach ($sites as $site) {
			$siteName = $site["siteName"];
			$siteUrl = $site["siteUrl"];
			
			print "<li><a href='$siteUrl'>$siteName</a></li>";
		}
		print "</ul>
		</div>";
	}
	print "</div>
	
	<div class='profile-main'>";
	if ($userBio) {
		print "<h2 class='block-title'>Biography</h2>
		<div class='block user-bio'>$userBio</div>";
	}
	print "<div class='user-activity'>
	<h2 class='block-title'>$userName's Activity</h2>
	<div class='user-actions'>";
	userActivity(NULL, NULL, $userId, NULL, 30, $db);
	print "</div>
	</div>
	</div>";
}

?>