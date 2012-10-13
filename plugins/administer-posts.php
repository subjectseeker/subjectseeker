<?php

/*

Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function adminPosts($httpQuery = NULL, $allowOverride = TRUE, $minimal = FALSE, $open = FALSE) {
  $db = ssDbConnect();
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
    $userPriv = getUserPrivilegeStatus($authUserId, $db);
		
		if ($userPriv > 1) { // admin
			$step = NULL;
			if (isset($_REQUEST["step"])) {
				$step = $_REQUEST["step"];
			}
			$arrange = "postId";
			$order = "descending";
			$pagesize = "30";
			$offset = "0";
			if (isset($_REQUEST["arrange"])) {
				$arrange = $_REQUEST["arrange"];
			}
			if (isset($_REQUEST["order"])) {
				$order = $_REQUEST["order"];
			}
			if (isset($_REQUEST["n"])) {
				$pagesize = $_REQUEST["n"];
			}
			if (isset($_REQUEST["offset"])) {
				$offset = $_REQUEST["offset"];
			}
			
			print "<div class=\"toggle-button\">Display Options</div>
			<div class=\"ss-slide-wrapper\">
			<div class=\"ss-div-2\" id=\"filter-panel\">
			<form method=\"get\">
			<input type=\"hidden\" name=\"filters\" value=\"filters\" />
			Sort by: <select name='arrange'>\n
			<option value='postId'";
			if ($arrange == "postId") {
				print " selected";
			}
			print ">ID</option>\n";
			print "<option value='postStatus'";
			if ($arrange == "postStatus") {
				print " selected";
			}
			print ">Status</option>\n";
			print "<option value='postTitle'";
			if ($arrange == "postTitle") {
				print " selected";
			}
			print ">Title</option>\n";
			print "<option value='postUri'";
			if ($arrange == "postUri") {
				print " selected";
			}
			print ">URI</option>\n";
			print "<option value='publicationTime'";
			if ($arrange == "publicationTime") {
				print " selected";
			}
			print ">Post Date</option>\n";
			print "<option value='ingestTime'";
			if ($arrange == "ingestTime") {
				print " selected";
			}
			print ">Added Date</option>\n";
			print "</select>\n";
			print " | <select name='order'>\n";
			print "<option value='ascending'";
			if ($order == "ascending") {
				print " selected";
			}
			print ">Ascending</option>\n";
			print "<option value='descending'";
			if ($order == "descending") {
				print " selected";
			}
			print ">Descending</option>\n
			</select><br />\n
			Entries per page: <input type=\"text\" name=\"n\" size=\"2\" value=\"$pagesize\"/> | Start at: <input type=\"text\" name=\"offset\" size=\"2\" value=\"$offset\"/><br />
			<input class=\"ss-button\" type=\"submit\" value=\"Go\" />
			</form>
			</div>
			</div>
			<br />";
				
			if ($step != NULL) {
				confirmEditPost($step, $db);
			}
			$queryList = httpParamsToSearchQuery($httpQuery, $allowOverride);
			$settings = httpParamsToExtraQuery($httpQuery, $allowOverride);
			$settings["type"] = "post";
			$postsData = generateSearchQuery ($queryList, $settings, 1, $db);
			if (empty($postsData["result"])) {
				print "<p>There are no more posts in the system.</p>";
			}
			else {
				editPostForm ($postsData["result"], $userPriv, $minimal, FALSE, $db);
			}
			global $pages;
			pageButtons ($pages["administer-posts"]->getAddress(), $pagesize, $postsData["total"]);
		} else { // not moderator or admin
			print "You are not authorized to administrate posts.<br />";
		}
  } else { // not logged in
    print "Please log in.";
  }
}
?>