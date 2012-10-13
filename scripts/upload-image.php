<?php

include_once("initialize.php");

function updateRecImage ($imageName, $postId, $userId, $db) {
	$imageName = mysql_real_escape_string($imageName);
	
	$sql = "UPDATE POST_RECOMMENDATION SET REC_IMAGE = '$imageName' WHERE BLOG_POST_ID = '$postId' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}

function updateUserAvatar ($imageName, $userId, $db) {
	$sql = "UPDATE USER SET USER_AVATAR_LOCATOR = '$imageName' WHERE USER_ID = '$userId'";
	mysql_query($sql, $db);
}

if (isLoggedIn()){
	global $imagesUrl;
	global $imagedir;
	
	// Connect to database
	$db = ssDbConnect();
	
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserName = $authUser->userName;
	$userPriv = getUserPrivilegeStatus($authUserId, $db);
	
	$userId = $authUserId;
	if ($userPriv > 0 && isset($_POST["userId"])) {
		$userId = $_POST["userId"];
	}
	// Get basic image details
	$imageName = $_POST["imageName"];
	$imageInfo = pathinfo($imageName);
	$extension = $imageInfo["extension"];
	$name = $imageInfo["filename"];
	
	$imageLocation = "$imagesUrl/tmp/$imageName";
	
	$x = $_POST["x"];
	$y = $_POST["y"];
	$w = $_POST["w"];
	$h = $_POST["h"];
	
	if ($extension == "jpg" || $extension == "JPG" || $extension == "jpeg") {
		$img_r = imagecreatefromjpeg($imageLocation);
	}
	elseif ($extension == "png" || $extension == "PNG") {
		$img_r = imagecreatefrompng($imageLocation);
	}
	elseif ($extension == "gif" || $extension == "GIF") {
		$img_r = imagecreatefromgif($imageLocation);
	}
	
	if ($userPriv > 0 && isset($_POST["type"]) && $_POST["type"] == "header") {	
		// Generate cropped image
		$createImage = imagecreatetruecolor(580, 200);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,580,200,$w,$h);
	}
	else {
		// Generate cropped image
		$createImage = imagecreatetruecolor(300, 300);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,300,300,$w,$h);
		
		// Generate small cropped image
		$createImageS = imagecreatetruecolor(50, 50);
		imagecopyresampled($createImageS,$img_r,0,0,$x,$y,50,50,$w,$h);
	}
	
	$newImageName = $name.".jpg";
	
	if ($userPriv > 0 && isset($_POST["type"]) && $_POST["type"] == "header") {
		$postId = $_POST["postId"];
		imagejpeg($createImage, "$imagedir/headers/$newImageName", 	100);
		updateRecImage($newImageName, $postId, $userId, $db);
	}
	else {
		$newImageNameS = "small-".$name.".jpg";
		
		// If user image folder doesn't exist, create it.
		if (!is_dir($imagedir."/users/".$userId)) {
			mkdir($imagedir."/users/".$userId."/avatars", 0775, TRUE);
			chmod($imagedir."/users/".$userId, 0775);
			chmod($imagedir."/users/".$userId."/avatars", 0775);
		}
		// Create and save image.
		imagejpeg($createImage, "$imagedir/users/$userId/avatars/$newImageName", 	100);
		updateUserAvatar ($newImageName, $userId, $db);
		
		// Create and save small image.
		imagejpeg($createImageS, "$imagedir/users/$userId/avatars/$newImageNameS", 	100);
		
		imagedestroy($createImageS);
	}
	
	imagedestroy($createImage);
}

global $homeUrl;
$originalUrl = $homeUrl;
if (isset($_REQUEST["url"])) {
	$originalUrl = $_REQUEST["url"];
}
header("Location: ".$originalUrl);

?>