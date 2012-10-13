<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once (dirname(__FILE__)."/../initialize.php");

// Only recommend if user is logged in.
if (isLoggedIn()){
	$db = ssDbConnect();
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserName = $authUser->userName;
	$userPriv = getUserPrivilegeStatus($authUserId, $db);
	$postId = str_replace("post-", "", $_REQUEST["postId"]);
	
	// If user is logged in
	if ($authUserId != NULL) {
		$step = NULL;
		if (isset($_REQUEST["step"])) {
			$step = $_REQUEST["step"];
		}
		
		if ($step == 'recommend') {
			// Insert recommendation
			$timestamp = dateStringToSql("now");
			$sql = "INSERT IGNORE INTO POST_RECOMMENDATION (USER_ID, BLOG_POST_ID, REC_DATE_TIME) VALUES ($authUserId, $postId, '$timestamp')";
			mysql_query($sql, $db);
			
			if ($userPriv > 0) {
				$sql = "UPDATE POST_RECOMMENDATION SET REC_NOTIFICATION='1' WHERE BLOG_POST_ID='$postId' AND USER_ID='$authUserId' AND REC_NOTIFICATION = '0'";
				mysql_query($sql, $db);
			}
		}
		
		if ($step == 'recommended') {	
			$sql = "DELETE FROM POST_RECOMMENDATION WHERE BLOG_POST_ID = $postId AND USER_ID = $authUserId";
			mysql_query($sql, $db);
		}
		// Get post recommendation status
		$recStatus = getRecommendationsCount($postId, NULL, $authUserId, NULL, $db);
	}
	
	// Get number of recommendations for this post
	$recCount = getRecommendationsCount($postId, NULL, NULL, NULL, $db);
	
	// Update recommendation button.
	if ($recStatus == TRUE) {
		print "<div class=\"recommended\" title=\"Remove recommendation and note\"></div>";
	} else  {
		print "<div class=\"recommend\" title=\"Recommend\"></div>";
	}
	print "<span class=\"rec-count\">$recCount</span>";
}

?>