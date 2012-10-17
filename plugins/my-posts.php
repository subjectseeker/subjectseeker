<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayMyPosts($minimal = FALSE, $open = FALSE) {
	global $pages;
	if (isLoggedIn()){
		// Connect to DB.
		$db  = ssDbConnect();
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		$step = NULL;
		if (!empty($_REQUEST["step"])) {
			$step = $_REQUEST["step"];
		}
		
		// Filter panel values.
		$arrange = "publicationTime";
		$order = "descending";
		$pagesize = "15";
		$offset = "0";
		
		if (!empty($_GET["arrange"])) {
			$arrange = $_GET["arrange"];
		}
		if (!empty($_GET["order"])) {
			$order = $_GET["order"];
		}
		if (!empty($_GET["n"])) {
			$pagesize = $_GET["n"];
		}
		if (!empty($_GET["offset"])) {
			$offset = $_GET["offset"];
		}
		if (!empty($_GET["blog"])) {
			$blogId = $_GET["blog"];
		}
		
		else {
			if (!empty($step)) {
				confirmEditPost($step, $db);
			}
			
			$queryList = httpParamsToSearchQuery("filter0=author&modifier0=user-name&value0=".$authUserName, FALSE);
			$settings = httpParamsToExtraQuery("filter0=author&modifier0=user-name&value0=".$authUserName, FALSE);
			$settings["type"] = "post";
			$postsData = generateSearchQuery ($queryList, $settings, 1, $db);
			if (empty($postsData["result"])) {
				print "<p>There are no more posts in the system.</p>";
			}
			else {
				editPostForm ($postsData["result"], $userPriv, FALSE, $db);
			}
			$limit = NULL;
			if (!empty($_REQUEST["n"])) {
				$limit = $_REQUEST["n"];
			}
			pageButtons ($pages["posts"]->getAddress(), $limit, $postsData["total"]);
		}
	}
	else { // Not logged in
		print "<p class=\"ss-warning\">Please log in.</p>";
	}
}

?>
