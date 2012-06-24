<?php
include_once "ss-globals.php";
include_once "ss-util.php";
global $wpLoad;
include_once $wpLoad;
// Connect to database
$db = ssDbConnect();

if (is_user_logged_in()){
	global $current_user;
	get_currentuserinfo();
	$displayName = $current_user->user_login;
	$email = $current_user->user_email;
	$userId = addUser($displayName, $email, $db);
	$userPriv = getUserPrivilegeStatus($userId, $db);
}
$postId = $_REQUEST["postId"];

$imageName = $_REQUEST["imageName"];
$targ_w = 580;
$targ_h = 200;

$imageInfo = pathinfo($imageName);
$extension = $imageInfo["extension"];
$name = $imageInfo["filename"];

global $imagesUrl;
$imageLocation = "$imagesUrl/tmp/$imageName";

if ($extension == "jpg") {
	$img_r = imagecreatefromjpeg($imageLocation);
}
elseif ($extension == "png") {
	$img_r = imagecreatefrompng($imageLocation);
}
elseif ($extension == "gif") {
	$img_r = imagecreatefromgif($imageLocation);
}
$dst_r = imagecreatetruecolor( $targ_w, $targ_h );

imagecopyresampled($dst_r,$img_r,0,0,$_POST['x'],$_POST['y'],$targ_w,$targ_h,$_POST['w'],$_POST['h']);

global $imagedir;
$newImageName = $name.".jpg";
imagejpeg($dst_r, "$imagedir/headers/$newImageName", 	100);

$imgName = $image["name"];

$sql = "UPDATE RECOMMENDATION SET REC_IMAGE = '$newImageName' WHERE BLOG_POST_ID = '$postId' AND USER_ID = '$userId'";
mysql_query($sql, $db);

global $homeUrl;
header("Location: ".$homeUrl);

?>