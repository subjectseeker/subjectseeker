<?php
include_once "ss-util.php";
// Connect to database
$db = ssDbConnect();

$personaId = $_REQUEST["personaId"];
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

$sql = "UPDATE RECOMMENDATION SET REC_IMAGE = '$newImageName' WHERE BLOG_POST_ID = $postId AND PERSONA_ID = $personaId";
mysql_query($sql, $db);

global $homeUrl;
header("Location: ".$homeUrl);

?>