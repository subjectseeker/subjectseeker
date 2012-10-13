<?php

/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function approveSites() {
	$step = NULL;
  if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
  $db = ssDbConnect();

  if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
    $userPriv = getUserPrivilegeStatus($authUserId, $db);
	
    if ($userPriv > 0) { // moderator or admin
			if (!empty($step)) {
				confirmEditBlog ($step, $db);
			}
			$queryList = httpParamsToSearchQuery("type=blog&filter0=status&value0=1&sort=added-date&order=desc");
			$settings = httpParamsToExtraQuery("type=blog&filter0=status&value0=1&sort=added-date&order=desc");
			$settings["type"] = "blog";
			$blogData = generateSearchQuery ($queryList, $settings, $userPriv, $db);
			
			if (!empty($blogData["result"])) {
				while ($row = mysql_fetch_array($blogData["result"])) {
					editBlogForm ($row, $userPriv, TRUE, $db);
				}
			}
			else {
				print "<div class=\"padding-content\">There are no sites pending approval.</div>";
			}
    } else { # not moderator or admin
      print "<p class=\"ss-warning\">You are not authorized to view the list of blogs for approval.</p>";
    }
  } else {
    print "<p class=\"ss-warning\">Please log in.</p>";
  }
}
?>
