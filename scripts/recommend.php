<?php
include_once "ss-globals.php";
include_once "ss-util.php";
global $imagesUrl;

// Connect to database
$db = ssDbConnect();

$personaId = $_REQUEST["persona"];
// Separate post ID from URL
preg_match('/\d+$/', $_REQUEST["id"], $matchResult);
$postId = implode($matchResult);

// If user is logged in
if ($personaId != NULL) {
	$step = $_REQUEST["step"];
	
	if ($step == 'recommend') {
		// Insert recommendation
		$timestamp = dateStringToSql("now");
		$sql = "INSERT IGNORE INTO RECOMMENDATION (PERSONA_ID, BLOG_POST_ID, REC_DATE_TIME) VALUES ($personaId, $postId, '$timestamp')";
		mysql_query($sql, $db);
	}
	
	if ($step == 'remove') {	
		$sql = "DELETE FROM RECOMMENDATION WHERE BLOG_POST_ID = $postId AND PERSONA_ID = $personaId";
		mysql_query($sql, $db);
	}
	// Get post recommendation status
	$recStatus = getRecommendationsCount($postId, NULL, $personaId, NULL, $db);
}

// Get number of recommendations for this post
$recCount = getRecommendationsCount($postId, NULL, NULL, NULL, $db);

// Update recommendation button.
if ($recStatus == TRUE) {
	print "<div class=\"recommend\" id=\"remove\" title=\"Remove recommendation and note\" style=\"background-image: url($imagesUrl/icons/ss-sprite.png); height: 18px; background-position: center -19px; background-repeat: no-repeat;\"></div>
	$recCount";
}
else {
	print "<div class=\"recommend\" id=\"recommend\" title=\"Recommend\" style=\"background-image: url($imagesUrl/icons/ss-sprite.png); height: 18px; background-position: center 0px; background-repeat: no-repeat;\"></div>
	$recCount";
}

?>