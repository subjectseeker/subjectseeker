<?php
include_once "ss-util.php";
// Connect to database
$db = ssDbConnect();

$personaId = $_REQUEST["personaId"];
// Separate post ID from URL
preg_match('/\d+$/', $_REQUEST["postId"], $matchResult);
$postId = implode($matchResult);

$image = $_FILES["image"];
global $imagedir;
move_uploaded_file($image["tmp_name"], "$imagedir/headers/" . $image["name"]);

if ((($image["type"] == "image/gif") || ($image["type"] == "image/jpeg") || ($image["type"] == "image/pjpeg") || ($image["type"] == "image/png")) && ($image["size"] < 1048576)) {
	if ($image["error"] > 0) {
		print "<p>Error: " . $image["error"] . "</p>";
	}
	else {
		global $imagedir;
		move_uploaded_file($image["tmp_name"], "$imagedir/headers/" . rand(0, 200)  . $image["name"]);
		
		$imgName = $image["name"];
		$sql = "UPDATE RECOMMENDATION SET REC_IMAGE = '$imgName' WHERE BLOG_POST_ID = $postId AND PERSONA_ID = $personaId";
		mysql_query($sql, $db);
	}
}
global $homeUrl;
header( 'Location: '.$homeUrl);
?>