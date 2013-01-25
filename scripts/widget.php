<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/util.php");

function displayWidget() {
	global $homeUrl;
	global $pages;
	
	$db = ssDbConnect();
	$api = new API;
	$api->searchDb("n=6", TRUE, "post");
	
	if ($api->errors) {
		foreach ($api->errors as $error) {
			print "<p class=\"ss-error\">$error</p>";
		}
		
	} else {
		global $sitename;
		print "<div class=\"widget-title\"><a title=\"Go to $sitename\" href=\"$homeUrl\" target=\"_blank\" >Now on<br />
		<img class=\"logo-wrapper\" src=\"$homeUrl/images/logos/SSLogoSmall.png\" alt=\"$sitename Logo\" /></a></div>
		<div id=\"posts-wrapper\">";
		
		if ($api->total == 0) {
			print "<div class=\"padding-content\">No results found for your search parameters.</div>";
		}
		
		foreach ($api->posts as $post) {
			$postId = $post["postId"];
			$blogName = $post["siteName"];
			$blogUri = $post["siteUrl"];
			$postTitle = $post["postTitle"];
			$postUri = $post["postUrl"];
			
			// If post doesn't have a title, use the url instead.
			if (! $postTitle)
				$postTitle = $postUri;
			
			print "<div class=\"ss-entry-wrapper\">
			<a class=\"postTitle\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a><br /><a target=\"_blank\" class=\"blogTitle\" title=\"$blogName homepage\" href=\"$blogUri\">$blogName</a>
			</div>";
		}
		print "</div>";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Widget | <?php echo $sitename ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl; ?>/widget.css" media="all">
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
<?php
displayWidget();
?>
</div>
</body>
</html>

?>
