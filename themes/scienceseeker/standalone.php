<?php
global $currentPage;
global $homeUrl;
global $themeUrl;
global $localStylesheet;

$content = "";
foreach ($currentPage->getLocations("center") as $tab) {
	$content .= displayModules($tab["modules"], TRUE);
}

if ($httpsEnabled == "true") {
	$themeUrl = str_replace("http:", "https:", $themeUrl);
	$imagesUrl = str_replace("http:", "https:", $imagesUrl);
}
?>
<!DOCTYPE html>
<html>
<head>
<?php include_once("head.php"); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl; ?>/standalone.css" media="all">
<?php
echo $customHead;
?>
</head>
<body>
<div id="wrapper">
<div class="medium-logo">
<a href="<?php echo $homeUrl ?>" title="Home"><img src="<?php echo $imagesUrl ?>/logos/SSMediumLogo.png" alt="Site logo" /></a>
</div>
<div id="content-box">
<?php
echo $content;
?>
</div>
</div>
</body>
</html>
