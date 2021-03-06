<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function pluginMyGroups() {
	global $homeUrl, $pages;
	
	$db	= ssDbConnect();
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
	} else {
		print "<p class=\"ss-warning\">Please log in.</p>";
		
		return NULL;
	}
	
	$step = NULL;
	if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	
	if ($step == "create") {
		$groupName = $_POST["name"];
		$groupDescription = $_POST["description"];
		
		if (empty($groupName)) {
			print "<p class=\"ss-error\">You must submit a name for your group.</p>
			<p><a href=\"".$pages["my-groups"]->getAddress()."\">Retry</a>";
			
			return NULL;
		}
		
		$groupId = addGroup($groupName, $groupDescription, $db);
		addGroupManager($groupId, $authUserId, 2, $db);
		
		if (isset($_POST["blog-network"])) {
			editGroup($groupId, $groupName, $groupDescription, 0, 1, $db);
			blogNetworkInfo($groupId);
			
			return NULL;
		}
		
		print "<div class=\"margin-bottom\">
		<h3>Tags to follow</h3>";
		displayTags($groupId, 4, TRUE, $db);
		print "</div>
		<div class=\"margin-bottom\">
		<h3>Group Banner</h3>
		<form method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$homeUrl/group/$groupId&amp;type=group-banner&groupId=$groupId\" enctype=\"multipart/form-data\">
		<div class=\"margin-bottom\"><input type=\"file\" name=\"image\" /></div>
		<input class=\"ss-button\" type=\"submit\" value=\"Upload Banner\" /> <a class=\"ss-button\" href=\"$homeUrl/group/$groupId\">Skip</a>
		</form>
		</div>";
		
	} elseif ($step == "select-sites") {
		$networkUrl = $_POST["network-url"];
		$groupId = $_POST["group-id"];
		$tagName = $_POST["tag-name"];
		
		if (!isGroupManager($groupId, $authUserId, NULL, $db)) {
			return NULL;
		}
		
		addTag($tagName, $groupId, 4, 3, $authUserId, TRUE, $db);
		
		print "<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"select-sites\" />
		<input type=\"hidden\" name=\"group-id\" value=\"$groupId\" />
		<input type=\"hidden\" name=\"tag-name\" value=\"$tagName\" />
		<div class=\"center-text\">
		<div class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"network-url\" value=\"$networkUrl\" /></div>
		<p><input class=\"big-button\" type=\"submit\" value=\"Modify Search\" /></p>
		</div>
		</form>";
		
		$api = new API;
		$api->searchDb("filter0=url&value0=$networkUrl&n=500", FALSE, "blog");
		$sites = $api->sites;
		print "<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"selected-sites\" />
		<input type=\"hidden\" name=\"group-id\" value=\"$groupId\" />
		<input type=\"hidden\" name=\"tag-name\" value=\"$tagName\" />
		<p><input class=\"checkall\" type=\"checkbox\" name=\"step\" value=\"selected-sites\" /> Select / Deselect All</p>
		<div class=\"entries\">";
		foreach ($sites as $site) {
			$blogId = $site["siteId"];
			$blogName = $site["siteName"];
			$blogUri = $site["siteUrl"];
			$blogSyndication = $site["siteFeedUrl"];
			$blogDescription = $site["siteSummary"];
			$blogTopics = getTags($blogId, 3, 1, $db);
			
			if (empty($blogDescription)) {
				$blogDescription = "No summary available for this site.";
			}
			
			print "<div class=\"ss-entry-wrapper\">
			<div class=\"entry-indicator\">+</div>
			<div class=\"post-header\">
			<input class=\"checkbox\" type=\"checkbox\" name=\"sites[]\" value=\"$blogId\" /> <a class=\"entry-title\" href=\"$homeUrl/site/$blogId\">".$blogName."</a>
			<div class=\"index-categories\">";
			foreach ($blogTopics as $i => $topic) {
				$topicName = $topic["topicName"];
				if ($i != 0)
					print " | ";
				print "<a href=\"".$pages["sources"]->getAddress()."/?type=blog&amp;filter0=topic&amp;value0=".urlencode($topicName)."\" title=\"View all posts in $topicName\">$topicName</a>";
			}
			print "</div>
			</div>
			<div class=\"ss-slide-wrapper\">
				<div class=\"entry-description\">$blogDescription</div>
				<div>
				<a class=\"ss-button\" href=\"$blogUri\">Home</a> <a class=\"ss-button\" href=\"".$pages["posts"]->getAddress()."/?type=posts&amp;filter0=blog&amp;modifier0=identifier&amp;value0=$blogId\">Posts</a> <a class=\"ss-button\" href=\"".$blogSyndication."\">Feed</a> <a class=\"ss-button\" href=\"$homeUrl/claim/".$blogId."\">Claim this site</a>
				</div>
			</div>
			</div>";
		}
		print "</div>
		<p><input class=\"ss-button\" type=\"submit\" value=\"Add Sites\" /></p>
		</form>";
		
	} elseif ($step == "create-network") {
		$groupId = $_REQUEST["groupId"];
		blogNetworkInfo($groupId);
		
	} elseif ($step == "selected-sites") {
		$siteIds = $_POST["sites"];
		$groupId = $_POST["group-id"];
		$tagName = $_POST["tag-name"];
		
		if (!isGroupManager($groupId, $authUserId, NULL, $db)) {
			return NULL;
		}
		
		foreach ($siteIds as $siteId) {
			addTag($tagName, $siteId, 3, 3, $authUserId, FALSE, $db);
		}
		
		print "<h3>Group Banner</h3>
		<form method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$homeUrl/group/$groupId&amp;type=group-banner&groupId=$groupId\" enctype=\"multipart/form-data\">
		<div class=\"margin-bottom\"><input type=\"file\" name=\"image\" /></div>
		<input class=\"ss-button\" type=\"submit\" value=\"Upload Banner\" /> <a class=\"ss-button\" href=\"$homeUrl/group/$groupId\">Skip</a>
		</form>";
		
	} elseif ($step == "delete") {
		$groupId = $_POST["groupId"];
		$group = getGroup($groupId, $db);
		$groupName = $group["groupName"];
		
		if (!isGroupManager($groupId, $authUserId, 2, $db)) {
			print "<p class=\"ss-error\">You don't have the required permissions to delete this group.</p>";
			
			return FALSE;
		}
		
		print "<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"delete-confirmed\" />
		<input type=\"hidden\" name=\"groupId\" value=\"$groupId\" />
		<p>Are you sure you want to delete $groupName? All followers will be lost.</p>
		<input class=\"ss-button\" type=\"submit\" value=\"Delete Group\" /> <a class=\"ss-button\" href=\"".$pages["my-groups"]->getAddress()."\">Back to My Groups</a>
		</form>";
	
	} else {
		if ($step) {
			$groupId = $_POST["groupId"];
			if ($step == "edit" && isGroupManager($groupId, $authUserId, NULL, $db)) {
				$groupName = $_POST["name"];
				$groupDescription = $_POST["description"];
				$groupMatchedPosts = 0;
				$groupMatchedSitePosts = 0;
				if ($_POST["track-posts"] == "sites") {
					$groupMatchedSitePosts = 1;
				} else {
					$groupMatchedPosts = 1;
				}
				editGroup($groupId, $groupName, $groupDescription, $groupMatchedPosts, $groupMatchedSitePosts, $db);
				
				print "<p class=\"ss-successful\">$groupName has been successfully updated.</p>";
				
			} elseif ($step == "delete-confirmed" && isGroupManager($groupId, $authUserId, 2, $db)) {
				$groupId = $_POST["groupId"];
				deleteGroup($groupId, $db);
				print "<p class=\"ss-successful\">Group successfully deleted.</p>";
				
			} elseif ($step == "add-manager" && isGroupManager($groupId, $authUserId, NULL, $db)) {
				$userName = $_POST["manager-name"];
				$userId = getUserId($userName, $db);
				
				if ($userId) {
					addGroupManager($groupId, $userId, 1, $db);
					print "<p class=\"ss-successful\">$userName has been added as a manager.</p>";
				} else {
					print "<p class=\"ss-error\">Unknown user: $userName.</p>";
				}
			}
		}
		
		$groups = getManagerGroups($authUserId, $db);
		print "<h3>Manage Groups</h3>";
		if (!$groups) {
			print "<p>No groups available. You can create a group below.</p>";
		}
		print "<div class=\"entries\">";
		foreach ($groups as $group) {
			manageGroup($group, $db);
		}
		print "</div>
		<hr />
		<h3>Create Group</h3>
		<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"create\" />
		<div class=\"margin-bottom\">Name<br />
		<input type=\"text\" name=\"name\" /></div>
		<div class=\"margin-bottom\">Description<br />
		<textarea name=\"description\"></textarea></div>
		<div class=\"margin-bottom\"><input type=\"checkbox\" name=\"blog-network\" value=\"1\" /> This is a blog network.</div>
		<div class=\"margin-bottom\"><input class=\"ss-button\" type=\"submit\" value=\"Create Group\" /></div>
		</form>";
	}
}

function blogNetworkInfo($groupId) {
	print "<form method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"select-sites\" />
	<input type=\"hidden\" name=\"group-id\" value=\"$groupId\" />
	<h3>Create a unique tag for your network</h3>
	<p><input type=\"text\" name=\"tag-name\" /><br />
	<span class=\"subtle-text\">Example: sa-blogs</span></p>
	<h3>Network base URL</h3>
	<p><input type=\"text\" name=\"network-url\" /><br />
	<span class=\"subtle-text\">Example: <em>http://blogs.scientificamerican.com/</em></span></p>
	<p><input class=\"big-button\" type=\"submit\" value=\"Next step\" /></p>
	</form>";
}

function editGroup($groupId, $groupName, $groupDescription, $groupMatchedPosts, $groupMatchedSitePosts, $db) {
	$groupName = mysql_real_escape_string($groupName);
	$groupDescription = mysql_real_escape_string($groupDescription);
	
	$sql = "UPDATE `GROUP` SET GROUP_NAME='$groupName', GROUP_DESCRIPTION = '$groupDescription', GROUP_MATCHING_POSTS = '$groupMatchedPosts', GROUP_MATCHING_SITES = '$groupMatchedSitePosts' WHERE GROUP_ID='$groupId'";
	mysql_query($sql, $db);
}

function deleteGroup($groupId, $db) {
	$sql = "DELETE FROM FOLLOWER WHERE OBJECT_ID = '$groupId' AND OBJECT_TYPE_ID = 4'";
	mysql_query($sql, $db);
	
	$sql = "DELETE FROM GROUP_MANAGER WHERE GROUP_ID = '$groupId'";
	mysql_query($sql, $db);
	
	$sql = "DELETE FROM TAG WHERE OBJECT_ID = '$groupId' AND OBJECT_TYPE_ID = '4'";
	mysql_query($sql, $db);
	
	$sql = "DELETE FROM COMMENT WHERE OBJECT_ID = '$groupId' AND OBJECT_TYPE_ID = '4'";
	mysql_query($sql, $db);
	
	$sql = "DELETE FROM RECOMMENDATION WHERE OBJECT_ID = '$groupId' AND OBJECT_TYPE_ID = '4'";
	mysql_query($sql, $db);
	
	$sql = "DELETE FROM `GROUP` WHERE GROUP_ID = '$groupId'";
	mysql_query($sql, $db);
}

function manageGroup($group, $db) {
	global $homeUrl, $pages;
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$currentUrl = getURL();
	
	$groupId = $group["groupId"];
	$groupName = $group["groupName"];
	$groupDescription = $group["groupDescription"];
	
	print "<div class=\"ss-entry-wrapper\">
	<div class=\"entry-indicator\">+</div>
	<div class=\"post-header\">
	<a class=\"entry-title\" href=\"$homeUrl/group/$groupId\">$groupName</a>
	</div>
	<div class=\"ss-slide-wrapper\">
	<br />
	<form class=\"block\" method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"edit\" />
	<input type=\"hidden\" name=\"groupId\" value=\"$groupId\" />
	<p>Name<br/ >
	<input type=\"text\" name=\"name\" value=\"".htmlspecialchars($groupName, ENT_QUOTES)."\"/></p>
	<p>Description<br />
	<textarea name=\"description\">".htmlspecialchars($groupDescription)."</textarea></p>";
	$groupTags = getTags($groupId, 4, 3, $db);
	print "<h3>Tags</h3>";
	displayTags($groupId, 4, TRUE, $db);
	print "<p><input type=\"radio\" name=\"track-posts\" value=\"posts\"";
	if ($group["groupMatchedPosts"]) {
		print "checked=\"checked\"";
	}
	print " /> Group posts that match these tags.</p>
	<p><input type=\"radio\" name=\"track-posts\" value=\"sites\"";
	if ($group["groupMatchedSitePosts"]) {
		print "checked=\"checked\"";
	}
	print " /> Group posts from sources that match these tags.</p>
	<div class=\"margin-bottom\"><input class=\"ss-button\" type=\"submit\" value=\"Save Changes\" /></div>
	</form>
	<hr />
	<h3>Group Banner</h3>
	<form class=\"block\" method=\"post\" action=\"".$pages["crop"]->getAddress()."/?url=$currentUrl&amp;type=group-banner&groupId=$groupId\" enctype=\"multipart/form-data\">
	<div class=\"margin-bottom\"><input type=\"file\" name=\"image\" /> <input class=\"ss-button\" type=\"submit\" value=\"Upload\" /></div>
	</form>
	<hr />";
	$managers = getGroupManagers($groupId, NULL, $db);
	print "<h3>Managers (".count($managers).")</h3>
	<div class=\"margin-bottom\">";
	foreach ($managers as $manager) {
		$userId = $manager["userId"];
		$userName = getUserName($userId, $db);
		$userAvatar = getUserAvatar($userId, $db);
		print "<div class=\"user-card-small\" data-user-id=\"$userId\" data-group-id=\"$groupId\"><a href=\"$homeUrl/user/$userName\"><img height=\"25\" src=\"".$userAvatar["small"]."\" /> <span>$userName</span></a>";
		if (isGroupManager($groupId, $authUserId, 2, $db) && $userId != $authUserId) {
			print "<span class=\"user-remove\">X</span>";
		}
		print "</div>";
	}
	print "</div>
	<form class=\"block\" method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"add-manager\" />
	<input type=\"hidden\" name=\"groupId\" value=\"$groupId\" />
	<div class=\"margin-bottom\">User Name<br />
	<input type=\"text\" name=\"manager-name\" /></div>
	<div class=\"margin-bottom\"><input class=\"ss-button\" type=\"submit\" value=\"Add Manager\" /></div>
	</form>
	<hr />";
	if (isGroupManager($groupId, $authUserId, 2, $db)) {
		print "<form class=\"block\" method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"delete\" />
		<input type=\"hidden\" name=\"groupId\" value=\"$groupId\" />
		<a class=\"ss-button\" href=\"".$pages["my-groups"]->getAddress()."/?step=create-network&groupId=$groupId\">Blog Network Creation</a> <input class=\"ss-button\" type=\"submit\" value=\"Delete Group\" />
		</form>";
	}
	print "</div>
	</div>";
}

function getManagerGroups($userId, $db) {
	$sql = "SELECT * FROM `GROUP` g INNER JOIN GROUP_MANAGER gm ON g.GROUP_ID = gm.GROUP_ID WHERE gm.USER_ID = '$userId'";
	$results = mysql_query($sql, $db);
	
	$groups = array();
	while ($row = mysql_fetch_array($results)) {
		$group["groupId"] = $row["GROUP_ID"];
		$group["groupName"] = $row["GROUP_NAME"];
		$group["groupDescription"] = $row["GROUP_DESCRIPTION"];
		$group["groupMatchedPosts"] = $row["GROUP_MATCHING_POSTS"];
		$group["groupMatchedSitePosts"] = $row["GROUP_MATCHING_SITES"];
		$group["groupCreationDate"] = $row["CREATION_DATE_TIME"];
		
		array_push($groups, $group);
	}
	
	return $groups;
}

function getGroups($groupId, $db) {
	$sql = "SELECT * FROM `GROUP` WHERE GROUP_ID = '$groupId'";
	$result = mysql_query($sql, $db);
	
	$groups = array();
	while ($row = mysql_fetch_array($results)) {
		$row = mysql_fetch_array($result);
		$group["groupId"] = $row["GROUP_ID"];
		$group["groupName"] = $row["GROUP_NAME"];
		$group["groupDescription"] = $row["GROUP_DESCRIPTION"];
		$group["groupCreationDate"] = $row["CREATION_DATE_TIME"];
		
		array_push($groups, $group);
	}
	
	return $group;
}

function addGroup($groupName, $groupDescription, $db) {
	$groupName = mysql_real_escape_string($groupName);
	$groupDescription = mysql_real_escape_string($groupDescription);
	
	$sql = "INSERT INTO `GROUP` (GROUP_NAME, GROUP_DESCRIPTION, CREATION_DATE_TIME) VALUES ('$groupName', '$groupDescription', NOW())";
	mysql_query($sql, $db);
	
	return mysql_insert_id();
}

function addGroupManager($groupId, $userId, $managerPrivId, $db) {
	$sql = "REPLACE INTO GROUP_MANAGER (GROUP_ID, USER_ID, MANAGER_PRIVILEGE_ID) VALUES ('$groupId', '$userId', '$managerPrivId')";
	mysql_query($sql, $db);
	
	return mysql_insert_id();
}

?>
