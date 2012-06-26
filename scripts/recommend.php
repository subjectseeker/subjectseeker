<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once "ss-util.php";
global $wpLoad;
include_once $wpLoad;

// Only recommend if user is logged in.
if (is_user_logged_in()) {
	// Connect to database
	$db = ssDbConnect();
	global $current_user;
	get_currentuserinfo();
	$displayName = $current_user->user_login;
	$email = $current_user->user_email;
	$userId = addUser($displayName, $email, $db);
	
	$postId = $_REQUEST["id"];
	
	// If user is logged in
	if ($userId != NULL) {
		$step = $_REQUEST["step"];
		
		if ($step == 'recommend') {
			// Insert recommendation
			$timestamp = dateStringToSql("now");
			$sql = "INSERT IGNORE INTO RECOMMENDATION (USER_ID, BLOG_POST_ID, REC_DATE_TIME) VALUES ($userId, $postId, '$timestamp')";
			mysql_query($sql, $db);
		}
		
		if ($step == 'remove') {	
			$sql = "DELETE FROM RECOMMENDATION WHERE BLOG_POST_ID = $postId AND USER_ID = $userId";
			mysql_query($sql, $db);
		}
		// Get post recommendation status
		$recStatus = getRecommendationsCount($postId, NULL, $userId, NULL, $db);
	}
	
	// Get number of recommendations for this post
	$recCount = getRecommendationsCount($postId, NULL, NULL, NULL, $db);
	
	// Update recommendation button.
	if ($recStatus == TRUE) {
		print "<div class=\"red-star\" id=\"remove\" title=\"Remove recommendation and note\"></div>
		$recCount";
	}
	else {
		print "<div class=\"grey-star\" id=\"recommend\" title=\"Recommend\"></div>
		$recCount";
	}
}

?>