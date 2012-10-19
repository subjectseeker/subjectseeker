<?php

/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function displayMySites() {
	if (isLoggedIn()) {
		$db = ssDbConnect();
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
		$step = NULL;
	
		if (!empty($_REQUEST["step"])) {
			$step = $_REQUEST["step"];
		}
		if (!empty($_REQUEST["blogId"])) {
			$blogId = $_REQUEST["blogId"];
		}
		
		if (!empty($step)) {
			confirmEditBlog ($step, $db);
		}
		
		$blogIds = getBlogIdsByUserId($authUserId, $db);
		if (sizeof($blogIds) == 0) {
			$pendingBlogs = getUserPendingBlogs ($authUserId, $db);
			if (!empty($pendingBlogs)) {
				global $sitename;
				print "<p class=\"ss-warning\">You have no sites available for viewing. One site awaiting approval by a $sitename Editor. You will receive an email message when your site has been approved.</p>";
				return;
			} else {
				print "<p class=\"ss-warning\">You have no sites available for viewing.</p>";
				return;
			}
		}
	
		$blogData = blogIdsToBlogData($blogIds, $db);
		
		while ($row = mysql_fetch_array($blogData)) {
			editBlogForm($row, $userPriv, TRUE, $db);
		}

  } else {
    print "<p class=\"ss-warning>You must log in before you can edit your blog.</p>\n";
  }
}

// Input: user ID, DB handle
// Output: ids of blogs owned by this user
function getUserPendingBlogs ($userId, $db) {

  $sql = "select ba.BLOG_ID, user.DISPLAY_NAME from USER user, BLOG_AUTHOR ba, BLOG pa where user.USER_ID=$userId and ba.USER_ID=user.USER_ID and pa.BLOG_STATUS_ID=1 and pa.BLOG_ID=ba.BLOG_ID";
  $results = mysql_query($sql, $db);
  $blogIds = array();
  if ($results != null) {
    while ($row = mysql_fetch_array($results)) {
      array_push($blogIds, $row["BLOG_ID"]);
    }
  }
  return $blogIds;
}

?>
