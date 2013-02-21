
<?php

/*

Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function pluginListGroups($httpQuery = NULL, $allowOverride = TRUE, $minimal = FALSE, $open = FALSE, $userGroups = TRUE) {
	global $homeUrl, $pages;
	
	$db = ssDbConnect();
	$groups = NULL;
	$authUserId = NULL;
	$limit = 30;
	$offset = 0;
	
	if (isset($_REQUEST["n"]))
		$limit = $_REQUEST["n"];
	if (isset($_REQUEST["offset"]))
		$offset = $_REQUEST["offset"];
		
	if (isLoggedIn() && $userGroups){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		
		$groups = getUserGroups($authUserId, $limit, $offset, $db);
		$total = array_shift(mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()", $db)));
	}
	
	if ($authUserId && $groups) {
		print "<h1>Followed Groups</h1>
		<div class=\"entries\">";
		foreach ($groups as $group) {
			displayGroup($group, $db);
		}
		print "</div>";
	} else {
		$groups = getLatestGroups($limit, $offset, $db);
		$total = array_shift(mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()", $db)));
		print "<h1>Latest Groups</h1>
		<div class=\"entries\">";
		foreach ($groups as $group) {
			if (empty($group["groupName"])) {
				continue;
			}
			displayGroup($group, $db);
		}
		print "</div>";
	}
	
	if (!$minimal) {
		pageButtons ($pages["groups"]->getAddress(), $limit, $total);
	}
}

function getUserGroups($userId, $limit, $offset, $db) {
	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `GROUP` g INNER JOIN FOLLOWER f ON g.GROUP_ID = f.OBJECT_ID WHERE f.OBJECT_TYPE_ID = '4' AND f.USER_ID = '$userId' LIMIT $limit OFFSET $offset";
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

function getLatestGroups($limit, $offset, $db) {
	$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `GROUP` ORDER BY CREATION_DATE_TIME DESC LIMIT $limit OFFSET $offset";
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
