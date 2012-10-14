<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title>
<?php
global $sitename;
global $subtitle;
global $themeUrl;
global $jsUrl;
global $imagesUrl;
global $basedir;
global $pageName;
global $currentPage;

if (parse_url(getURL(),PHP_URL_PATH) == "/") {
	echo $sitename." | ".$subtitle;
}
else {
	echo $currentPage->title." | ".$sitename;
}

?></title>
<link rel="shortcut icon" href="<?php echo $imagesUrl ?>/misc/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl . "/style.css?v=" . filemtime($basedir.'/theme/style.css') ?>" media="all" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $jsUrl ?>/library.js"></script>