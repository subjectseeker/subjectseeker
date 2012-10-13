<?php
global $currentPage;
global $homeUrl;
global $themeUrl;
$content = displayModules($currentPage->getLocations("center"), TRUE);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php include_once("head.php"); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl; ?>/standalone.css" media="all">
</head>
<body>
<div id="wrapper">
<div class="center-text">
<a href="<?php echo $homeUrl ?>" title="Home page"><img style="margin: 30px 0px 50px 0px;" src="<?php echo $imagesUrl ?>/logos/SSMediumLogo.png" /></a>
</div>
<div id="content-box">
<?php
echo $content;
?>
</div>
</div>
</body>
</html>
