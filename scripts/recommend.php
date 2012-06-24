<?php
include_once "ss-globals.php";
include_once "ss-util.php";
include_once $wpLoad;
global $imagesUrl;

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