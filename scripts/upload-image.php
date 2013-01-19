<?php

include_once("initialize.php");

function updateRecImage ($imageName, $postId, $userId, $db) {
	$imageName = mysql_real_escape_string($imageName);
	
	$sql = "UPDATE RECOMMENDATION SET REC_IMAGE = '$imageName' WHERE OBJECT_ID = '$postId' AND OBJECT_TYPE_ID = '1' AND USER_ID = '$userId'";
	mysql_query($sql, $db);
}

function updateUserAvatar ($imageName, $userId, $db) {
	$sql = "UPDATE USER SET USER_AVATAR_LOCATOR = '$imageName' WHERE USER_ID = '$userId'";
	mysql_query($sql, $db);
}

function updateGroupBanner ($imageName, $groupId, $db) {
	$sql = "UPDATE `GROUP` SET GROUP_BANNER = '$imageName' WHERE GROUP_ID = '$groupId'";
	mysql_query($sql, $db);
}

function updateUserBanner ($imageName, $userId, $db) {
	$sql = "UPDATE USER_PREFERENCE SET USER_BANNER = '$imageName' WHERE USER_ID = '$userId'";
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
	
	$newImageName = $name.".jpg";
	
	if ($userPriv > 0 && isset($_POST["type"]) && $_POST["type"] == "header") {
		// Generate cropped image
		$createImage = imagecreatetruecolor(580, 200);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,580,200,$w,$h);
		
		$postId = $_POST["postId"];
		imagejpeg($createImage, "$imagedir/headers/$newImageName", 	100);
		updateRecImage($newImageName, $postId, $userId, $db);
		
	} elseif (isset($_POST["type"]) && $_POST["type"] == "user-banner") {
		$createImage = imagecreatetruecolor(1000, 125);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,1000,125,$w,$h);
		
		if (!is_dir($imagedir."/users/".$userId."/banners")) {
			mkdir($imagedir."/users/".$userId."/banners", 0775, TRUE);
			chmod($imagedir."/users/".$userId, 0775);
			chmod($imagedir."/users/".$userId."/banners", 0775);
		}
	
		imagejpeg($createImage, "$imagedir/users/$userId/banners/$newImageName", 	100);
		updateUserBanner ($imageName, $userId, $db);
	
	} elseif (isset($_POST["type"]) && $_POST["type"] == "group-banner") {
		$createImage = imagecreatetruecolor(1000, 125);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,1000,125,$w,$h);
		
		$groupId = $_POST["groupId"];	
		
		if (isGroupManager($groupId, $authUserId, NULL, $db)) {
			if (!is_dir($imagedir."/groups/".$groupId."/banners")) {
				mkdir($imagedir."/groups/".$groupId."/banners", 0775, TRUE);
				chmod($imagedir."/groups/".$groupId, 0775);
				chmod($imagedir."/groups/".$groupId."/banners", 0775);
			}
		
			imagejpeg($createImage, "$imagedir/groups/$groupId/banners/$newImageName", 	100);
			updateGroupBanner($newImageName, $groupId, $db);
		}
		
	} else {
		// Generate cropped image
		$createImage = imagecreatetruecolor(300, 300);
		imagecopyresampled($createImage,$img_r,0,0,$x,$y,300,300,$w,$h);
		
		// Generate small cropped image
		$createImageS = imagecreatetruecolor(50, 50);
		imagecopyresampled($createImageS,$img_r,0,0,$x,$y,50,50,$w,$h);
		
		$newImageNameS = "small-".$name.".jpg";
		
		// If user image folder doesn't exist, create it.
		if (!is_dir($imagedir."/users/".$userId."/avatars")) {
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