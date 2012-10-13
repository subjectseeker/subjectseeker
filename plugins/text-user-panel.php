<?php

function displayTextUserPanel () {
	$db = ssDbConnect();
	$currentUrl = getURL();
	global $pages;
	if (isLoggedIn()) {
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		$userTwitter = getUserSocialAccount(1, $authUserId, $db);
		
		print "<div id=\"user-box-footer\">
		<ul>
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
		</div>";
	}
	else {
		print "<div id=\"login-box-footer\">
		<ul>
		<li><a class=\"login-box-button\" href=\"".$pages["login"]->getAddress()."/?url=$currentUrl\">Log In</a><li> 
		<li><a class=\"login-box-button\" href=\"".$pages["register"]->getAddress()."/?url=$currentUrl\">Register</a><li>
		</ul>
		</div>";
	}
}
?>
