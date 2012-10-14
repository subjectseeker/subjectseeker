<?php

function displayUserPanel() {
	$db = ssDbConnect();
	$currentUrl = getURL();
	global $pages;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		$userTwitter = getUserSocialAccount(1, $authUserId, $db);
		$userAvatar = getUserAvatar($authUserId, $db);
		
		global $imagesUrl;
		if (isset($userAvatar)) {
			$avatarSrc = $imagesUrl."/users/$authUserId/avatars/small-$userAvatar";
		}
		else {
			$avatarSrc = $imagesUrl."/icons/small-default-avatar.jpg";
		}
		print "<div id=\"user-box\" data-user=\"$authUserId\">
		<div class=\"user-panel-avatar\"><img src=\"$avatarSrc\" /></div><div class=\"user-panel-options\"><div class=\"user-name\">$authUserName</div>
		<a class=\"user-panel-button\" href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a> <a class=\"user-panel-button\" href=\"".$pages["my-posts"]->getAddress()."\">My Posts</a></div>
		<div class=\"drop-down\">
		<div class=\"drop-down-title\">My Account</div>
		<ul class=\"user-panel\">
		<li class=\"user-panel-button\"><a href=\"".$pages["my-sites"]->getAddress()."\">My Sites</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["my-posts"]->getAddress()."\">My Posts</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["home"]->getAddress()."user/$authUserName/settings\">Settings</a></li>
		<li class=\"user-panel-button\"><a href=\"".$pages["login"]->getAddress()."/?logout=true&amp;url=$currentUrl\">Log Out</a></li>";
		if ($userPriv > 0) {
			print "<li class=\"user-panel-button\"><a href=\"".$pages["approve"]->getAddress()."\">Approve Sites</a></li>
			<li class=\"user-panel-button\"><a href=\"".$pages["administer-sources"]->getAddress()."\">Administer Sites</a></li>";
			if ($userPriv > 1){
				print "<li class=\"user-panel-button\"><a href=\"".$pages["administer-posts"]->getAddress()."\">Administer Posts</a></li>";
				print "<li class=\"user-panel-button\"><a href=\"".$pages["administer-users"]->getAddress()."\">Administer Users</a></li>";
			}
		}
		print "</ul>
		<div class=\"ss-div center-text\">";
		if ($userTwitter == TRUE) {
			$currentUrl = getURL();
			print "<div class=\"sync-link\"><a title=\"Go to Twitter profile\" href=\"https://twitter.com/#!/".$userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"]."\"><div class=\"twitter-icon\"></div> ".$userTwitter["SOCIAL_NETWORKING_ACCOUNT_NAME"]."</a></div>";
		}
		else {
			print "<div class=\"sync-link\"><a title=\"Go to synchronization page\" href=\"".$pages["twitter"]->getAddress()."\"><div class=\"twitter-icon\"></div> Sync Twitter</a></div>";
		}
		print "</div>
		</div>
		</div>";
	}
	else {
		print "<div id=\"login-box\">
		<a class=\"login-box-button\" href=\"".$pages["login"]->getAddress()."/?url=$currentUrl\">Log In</a> <a class=\"login-box-button\" href=\"".$pages["register"]->getAddress()."/?url=$currentUrl\">Register</a>
		</div>";
	}
}
?>
