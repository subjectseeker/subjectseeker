<?php

function displayUserPanel() {
	$db = ssDbConnect();
	$currentUrl = getURL();
	global $homeUrl, $imagesUrl, $pages;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		$userTwitter = getSocialNetworkUser(1, $authUserId, "userId", $db);
		$userAvatar = getUserAvatar($authUserId, $db);
		
		print "<div id=\"user-box\" data-user=\"$authUserId\">
		<div class=\"user-panel-avatar\"><img src='".$userAvatar["small"]."' /></div>
		<div class='alignleft'>
		<div class=\"user-name\"><a href='$homeUrl/user/$authUserName'>$authUserName</a></div>
		<div class=\"user-panel-options\"><a class=\"user-panel-button\" href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a> <a class=\"user-panel-button\" href=\"".$pages["my-posts"]->getAddress()."\">My Posts</a></div>
		</div>
		<div class=\"drop-down\">
		<div class=\"drop-down-title\">My Account</div>
		<ul class=\"user-panel\">
		<li class=\"user-panel-button\"><a href='$homeUrl/user/$authUserName'>My Profile</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-posts"]->getAddress()."\">My Posts</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-groups"]->getAddress()."\">My Groups</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["home"]->getAddress()."user/$authUserName/settings\">Settings</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["login"]->getAddress(TRUE)."/?logout=true&amp;url=$currentUrl\">Log Out</a></li>";
		if ($userPriv > 0) {
			print "<li class=\"user-panel-button\"><a href=\"".$pages["approve"]->getAddress()."\">Approve Sites</a></li>
			<li class=\"user-panel-button\"><a href=\"".$pages["administer-sources"]->getAddress()."\">Administer Sites</a></li>";
			if ($userPriv > 1){
				print "<li class=\"user-panel-button\"><a href=\"".$pages["administer-posts"]->getAddress()."\">Administer Posts</a></li>
				<li class=\"user-panel-button\"><a href=\"".$pages["administer-users"]->getAddress()."\">Administer Users</a></li>
				<li class=\"user-panel-button\"><a href=\"".$pages["add-post"]->getAddress()."\">Add Post</a></li>";
			}
		}
		print "</ul>
		</div>
		</div>";
	} else {
		print "<div id=\"login-box\">
		<a class=\"login-box-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?url=$currentUrl\">Log In</a> <a class=\"login-box-button\" href=\"".$pages["register"]->getAddress(TRUE)."/?url=$currentUrl\">Register</a>
		</div>";
	}
}

function displayTextUserPanel () {
	$db = ssDbConnect();
	$currentUrl = getURL();
	global $homeUrl, $pages;
	if (isLoggedIn()) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		print "<div id=\"user-box-footer\">
		<ul>
		<li class=\"user-panel-button\"><a href='$homeUrl/user/$authUserName'>My Profile</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-posts"]->getAddress()."\">My Posts</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-groups"]->getAddress()."\">My Groups</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["home"]->getAddress()."user/$authUserName/settings\">Settings</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["login"]->getAddress(TRUE)."/?logout=true&amp;url=$currentUrl\">Log Out</a></li>";
		if ($userPriv > 0) {
			print "<li class=\"user-panel-button\"><a href=\"".$pages["approve"]->getAddress()."\">Approve Sites</a></li>
			<li class=\"user-panel-button\"><a href=\"".$pages["administer-sources"]->getAddress()."\">Administer Sites</a></li>";
			if ($userPriv > 1){
				print "<li class=\"user-panel-button\"><a href=\"".$pages["administer-posts"]->getAddress()."\">Administer Posts</a></li>";
				print "<li class=\"user-panel-button\"><a href=\"".$pages["administer-users"]->getAddress()."\">Administer Users</a></li>";
			}
		}
		
		print "</ul>
		</div>";
	}
	else {
		print "<div id=\"login-box-footer\">
		<ul>
		<li><a class=\"login-box-button\" href=\"".$pages["login"]->getAddress(TRUE)."/?url=$currentUrl\">Log In</a><li> 
		<li><a class=\"login-box-button\" href=\"".$pages["register"]->getAddress(TRUE)."/?url=$currentUrl\">Register</a><li>
		</ul>
		</div>";
	}
}
?>
