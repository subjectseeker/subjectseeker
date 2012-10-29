<?php
global $currentPage;
global $homeUrl;
global $themeUrl;
global $localStylesheet;
$content = displayModules($currentPage->getLocations("center"), TRUE);
?>
<!DOCTYPE html>
<html>
<head>
<?php include_once("head.php"); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl; ?>/standalone.css" media="all">
<?php
if (!empty($localStylesheet)) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $localStylesheet ?>" media="all" />
<?php
}
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
