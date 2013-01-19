<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function adminPosts($httpQuery = NULL, $allowOverride = TRUE, $minimal = FALSE, $open = FALSE) {
	global $pages;
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
	
	if ($userPriv < 2) {
		print "<p class=\"ss-warning\">You don't have sufficient privileges.</p>";
		
		return NULL;
	}
	
	$step = NULL;
	if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	
	if ($step) {
		confirmEditPost($step, $db);
	}
	
	$api = new API;
	$api->searchDb($httpQuery, TRUE, "post", FALSE, $userPriv);
	$posts = $api->posts;
	
	if (empty($posts)) {
		print "<p>No posts found.</p>";
		return NULL;
	}
	
	editPostForm ($api->posts, $userPriv, FALSE, $db);
	$limit = NULL;
	if (isset($_REQUEST["n"])) {
		$limit = $_REQUEST["n"];
	}
	pageButtons ($pages["posts"]->getAddress(), $limit, $api->total);
}
?>