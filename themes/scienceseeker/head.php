<?php
global $homeUrl;
global $sitename;
global $subtitle;
global $feedUrl;
global $themeUrl;
global $jsUrl;
global $imagesUrl;
global $basedir;
global $currentPage;
global $localStylesheet;
global $debugSite;
global $httpsEnabled;

if ($currentPage->id == "home") {
	$title = $sitename." | ".$subtitle;
}
else {
	$title = $currentPage->title." | ".$sitename;
}

if ($httpsEnabled == "true") {
	$themeUrl = str_replace("http:", "https:", $themeUrl);
	$imagesUrl = str_replace("http:", "https:", $imagesUrl);
	$feedUrl = str_replace("http:", "https:", $feedUrl);
	$jsUrl = str_replace("http:", "https:", $jsUrl);
	$localStylesheet = str_replace("http:", "https:", $localStylesheet);
}

?>

<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>
<?php echo $title ?>
</title>
<link rel="shortcut icon" href="<?php echo $imagesUrl ?>/misc/favicon.ico" type="image/x-icon" />
<link rel="alternate" type="application/rss+xml" title="Feed | <?php echo $sitename; ?>" href="<?php echo $feedUrl; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl . "/style.css?v=" . filemtime($basedir.'/themes/scienceseeker/style.css') ?>" media="all" />
<?php
if (!empty($localStylesheet)) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $localStylesheet; ?>" media="all" />
<?php
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<?php
if ($debugSite == "true") {
?>
<meta name="robots" content="noindex, nofollow" />
<?php
}
?>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $jsUrl ?>/library.js"></script>