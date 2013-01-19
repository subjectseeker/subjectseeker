<?php

/*

Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function pluginListGroups() {
	global $homeUrl;
	global $pages;
	
	$db = ssDbConnect();
	$groups = NULL;
	$authUserId = NULL;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		
		$groups = getUserGroups($authUserId, $db);
	}
	
	if ($authUserId && $groups) {
		print "<h1>Followed Groups</h1>
		<div class=\"entries\">";
		foreach ($groups as $group) {
			displayGroup($group, $db);
		}
		print "</div>";
	} else {
		$groups = getLatestGroups($db);
		print "<h1>Latest Groups</h1>
		<div class=\"entries\">";
		foreach ($groups as $group) {
			displayGroup($group, $db);
		}
		print "</div>";
	}
}

function getUserGroups($userId, $db) {
	$sql = "SELECT * FROM `GROUP` g INNER JOIN FOLLOWER f ON g.GROUP_ID = f.OBJECT_ID WHERE f.OBJECT_TYPE_ID = '4' AND f.USER_ID = '$userId'";
	$results = mysql_query($sql, $db);
	
	$groups = array();
	while ($row = mysql_fetch_array($results)) {
		$group["groupId"] = $row["GROUP_ID"];
		$group["groupName"] = $row["GROUP_NAME"];
		$group["groupDescription"] = $row["GROUP_DESCRIPTION"];
		$group["groupCreationDate"] = $row["CREATION_DATE_TIME"];
		
		array_push($groups, $group);
	}
	
	return $groups;
}

function getLatestGroups($db) {
	$sql = "SELECT * FROM `GROUP` ORDER BY CREATION_DATE_TIME DESC";
	$results = mysql_query($sql, $db);
	
	$groups = array();
	while ($row = mysql_fetch_array($results)) {
		$group["groupId"] = $row["GROUP_ID"];
		$group["groupName"] = $row["GROUP_NAME"];
		$group["groupDescription"] = $row["GROUP_DESCRIPTION"];
		$group["groupCreationDate"] = $row["CREATION_DATE_TIME"];
		
		array_push($groups, $group);
	}
	
	return $groups;
}

?>
