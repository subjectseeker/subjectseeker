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
global $customHead;
global $debugSite;
global $httpsEnabled;

if ($currentPage->id == "home") {
	$title = $sitename." | ".$subtitle;
} elseif ($currentPage->id == "user-profile") {
	preg_match('/(?<=\/user\/)[A-Za-z][A-Za-z0-9_]+/', $_SERVER["REQUEST_URI"], $matchResult);
	$userName = $matchResult[0];
	$title = "$userName's Profile | ".$sitename;
	
} elseif ($currentPage->id == "post-profile") {
	$db = ssDbConnect();
	preg_match('/(?<=\/post\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$post = getPost ($matchResult[0], $db);
	$postTitle = $post["BLOG_POST_TITLE"];
	$title = "$postTitle | ".$sitename;
	
} elseif ($currentPage->id == "site-profile") {
	$db = ssDbConnect();
	preg_match('/(?<=\/site\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$siteName = getBlogName($matchResult[0], $db);
	$title = "$siteName | ".$sitename;
	
} elseif ($currentPage->id == "group-profile") {
	$db = ssDbConnect();
	preg_match('/(?<=\/group\/)\d+/', $_SERVER["REQUEST_URI"], $matchResult);
	$group = getGroup($matchResult[0], $db);
	$groupName = $group["groupName"];
	$title = "$groupName | ".$sitename;
	
} else {
	$title = $currentPage->title." | ".$sitename;
}

if ($httpsEnabled == "true") {
	$themeUrl = str_replace("http:", "https:", $themeUrl);
	$imagesUrl = str_replace("http:", "https:", $imagesUrl);
	$feedUrl = str_replace("http:", "https:", $feedUrl);
	$jsUrl = str_replace("http:", "https:", $jsUrl);
}
?>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<title><?php echo $title ?></title>
<link rel="shortcut icon" href="<?php echo $imagesUrl ?>/misc/favicon.ico" type="image/x-icon" />
<link rel="alternate" type="application/rss+xml" title="Feed | <?php echo $sitename; ?>" href="<?php echo $feedUrl; ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl . "/style.css?v=" . filemtime($basedir.'/themes/scienceseeker/style.css') ?>" media="all" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php
if ($debugSite == "true") {
?>
<meta name="robots" content="noindex, nofollow" />
<?php
}
?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $jsUrl ?>/library.js"></script>
<?php
echo $customHead;
?>